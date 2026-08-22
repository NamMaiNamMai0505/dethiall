<?php

namespace Modules\Lms\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsMaterial;
use Modules\Lms\Models\LmsScormPackage;
use ZipArchive;

class LmsMaterialService
{
    public const MAX_MB = 100;

    public const MAX_SCORM_ENTRIES = 5000;

    public const MAX_SCORM_UNCOMPRESSED_BYTES = 1024 * 1024 * 1024;

    /** @var list<string> */
    private const BLOCKED_SCORM_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'cmd', 'bat', 'ps1',
        'exe', 'dll', 'so', 'dylib', 'com', 'msi',
    ];

    public function detectKind(string $mime, string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, ['zip'], true) && str_contains(strtolower($filename), 'scorm')) {
            return 'scorm';
        }
        if ($ext === 'pdf' || $mime === 'application/pdf') {
            return 'pdf';
        }
        if (in_array($ext, ['ppt', 'pptx', 'odp'], true)) {
            return 'slide';
        }
        if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'mkv', 'mov'], true)) {
            return 'video';
        }
        if (str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'image';
        }
        if (in_array($ext, ['zip', 'rar', '7z'], true)) {
            return 'archive';
        }

        return 'document';
    }

    public function storeFile(LmsCourse $course, UploadedFile $file, array $meta = []): LmsMaterial
    {
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $original = $file->getClientOriginalName();
        $kind = $meta['kind'] ?? $this->detectKind($mime, $original);

        $dir = 'lms/courses/'.$course->id.'/materials';
        $stored = $file->store($dir, 'public');

        return LmsMaterial::create([
            'lms_course_id' => $course->id,
            'lms_lesson_id' => $meta['lms_lesson_id'] ?? null,
            'title' => $meta['title'] ?? pathinfo($original, PATHINFO_FILENAME),
            'description' => $meta['description'] ?? null,
            'kind' => $kind,
            'disk' => 'public',
            'path' => $stored,
            'original_name' => $original,
            'mime' => $mime,
            'size_bytes' => $file->getSize() ?: 0,
            'is_published' => (bool) ($meta['is_published'] ?? true),
            'sort_order' => (int) ($meta['sort_order'] ?? 0),
            'uploaded_by' => Auth::id(),
        ]);
    }

    /**
     * Upload ZIP SCORM: lưu material + extract + parse imsmanifest.xml (cơ bản).
     */
    public function storeScorm(LmsCourse $course, UploadedFile $file, array $meta = []): LmsScormPackage
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            throw new \InvalidArgumentException('Gói SCORM phải là file .zip');
        }

        $material = $this->storeFile($course, $file, array_merge($meta, [
            'kind' => 'scorm',
            'title' => $meta['title'] ?? ('SCORM: '.pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
        ]));

        $extractRel = 'lms/courses/'.$course->id.'/scorm/'.$material->id.'_'.Str::random(6);
        $extractAbs = Storage::disk('public')->path($extractRel);
        if (! is_dir($extractAbs)) {
            mkdir($extractAbs, 0755, true);
        }

        $zipPath = Storage::disk('public')->path($material->path);
        $zip = new ZipArchive;

        try {
            if ($zip->open($zipPath) !== true) {
                throw new \RuntimeException('Không mở được file ZIP SCORM.');
            }

            $this->validateScormArchive($zip);
            if (! $zip->extractTo($extractAbs)) {
                throw new \RuntimeException('Không giải nén được gói SCORM.');
            }
            $zip->close();

            [$version, $launch, $manifestRel] = $this->parseManifest($extractAbs, $extractRel);
            $launch = $this->validateLaunchPath($extractAbs, $launch);

            return LmsScormPackage::create([
                'lms_course_id' => $course->id,
                'lms_material_id' => $material->id,
                'title' => $meta['title'] ?? $material->title,
                'version' => $version,
                'launch_path' => $launch,
                'extract_path' => $extractRel,
                'manifest_path' => $manifestRel,
                'meta' => ['source' => $material->original_name],
                'is_published' => (bool) ($meta['is_published'] ?? true),
                'uploaded_by' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            if ($zip->status === ZipArchive::ER_OK) {
                $zip->close();
            }
            Storage::disk('public')->deleteDirectory($extractRel);
            Storage::disk('public')->delete($material->path);
            $material->forceDelete();

            throw $e;
        }
    }

    protected function validateScormArchive(ZipArchive $zip): void
    {
        if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_SCORM_ENTRIES) {
            throw new \RuntimeException('Gói SCORM rỗng hoặc vượt quá '.self::MAX_SCORM_ENTRIES.' tệp.');
        }

        $totalBytes = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
            if ($name === '' || str_contains($name, "\0")) {
                throw new \RuntimeException('Gói SCORM chứa tên tệp không hợp lệ.');
            }

            $segments = array_values(array_filter(explode('/', $name), fn (string $segment) => $segment !== ''));
            if (
                str_starts_with($name, '/')
                || preg_match('/^[A-Za-z]:\//', $name)
                || in_array('..', $segments, true)
            ) {
                throw new \RuntimeException('Gói SCORM chứa đường dẫn vượt ngoài thư mục cho phép.');
            }

            $totalBytes += max(0, (int) ($stat['size'] ?? 0));
            if ($totalBytes > self::MAX_SCORM_UNCOMPRESSED_BYTES) {
                throw new \RuntimeException('Dung lượng SCORM sau giải nén vượt quá 1 GB.');
            }

            if (! str_ends_with($name, '/')) {
                $basename = strtolower(basename($name));
                $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                if ($basename === '.htaccess' || $basename === 'web.config' || in_array($extension, self::BLOCKED_SCORM_EXTENSIONS, true)) {
                    throw new \RuntimeException("Gói SCORM chứa tệp thực thi không được phép: {$name}");
                }
            }

            $operatingSystem = 0;
            $attributes = 0;
            if ($zip->getExternalAttributesIndex($index, $operatingSystem, $attributes)) {
                $fileType = ($attributes >> 16) & 0xF000;
                if ($fileType === 0xA000) {
                    throw new \RuntimeException("Gói SCORM chứa symbolic link không được phép: {$name}");
                }
            }
        }
    }

    protected function validateLaunchPath(string $extractAbs, ?string $launch): string
    {
        $launch = str_replace('\\', '/', trim((string) $launch));
        $pathOnly = rawurldecode((string) parse_url($launch, PHP_URL_PATH));
        $segments = array_values(array_filter(explode('/', $pathOnly), fn (string $segment) => $segment !== ''));

        if (
            $launch === ''
            || parse_url($launch, PHP_URL_SCHEME) !== null
            || str_starts_with($pathOnly, '/')
            || in_array('..', $segments, true)
        ) {
            throw new \RuntimeException('Không tìm thấy launch path SCORM hợp lệ trong gói tải lên.');
        }

        $root = realpath($extractAbs);
        $target = realpath($extractAbs.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $pathOnly));
        if (! $root || ! $target || ! is_file($target) || ! str_starts_with($target, $root.DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('Launch path SCORM không tồn tại hoặc nằm ngoài gói tải lên.');
        }

        return ltrim($pathOnly, '/');
    }

    /**
     * @return array{0:?string,1:?string,2:?string} version, launch relative, manifest relative
     */
    protected function parseManifest(string $extractAbs, string $extractRel): array
    {
        $manifestAbs = $this->findFileRecursive($extractAbs, 'imsmanifest.xml');
        if (! $manifestAbs) {
            // fallback: first index.html
            $index = $this->findFileRecursive($extractAbs, 'index.html');
            $launch = $index ? ltrim(str_replace('\\', '/', substr($index, strlen($extractAbs))), '/') : null;

            return [null, $launch, null];
        }

        $xml = @simplexml_load_file($manifestAbs, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        $version = null;
        $launch = null;
        if ($xml !== false) {
            $attrs = $xml->attributes();
            $version = isset($attrs['version']) ? (string) $attrs['version'] : null;
            // SCORM 1.2 resource href
            $xml->registerXPathNamespace('imscp', 'http://www.imsproject.org/xsd/imscp_rootv1p1p2');
            $hrefs = $xml->xpath('//resource[@href]/@href')
                ?: $xml->xpath('//*[local-name()="resource"][@href]/@href');
            if ($hrefs && isset($hrefs[0])) {
                $launch = (string) $hrefs[0];
            }
            if (! $launch) {
                foreach ($xml->xpath('//*[local-name()="resource"]') ?: [] as $res) {
                    $h = (string) ($res['href'] ?? '');
                    if ($h !== '') {
                        $launch = $h;
                        break;
                    }
                }
            }
            // schemaversion
            $sv = $xml->xpath('//*[local-name()="schemaversion"]');
            if ($sv && isset($sv[0]) && ! $version) {
                $version = trim((string) $sv[0]);
            }
        }

        $manifestRel = ltrim(str_replace('\\', '/', substr($manifestAbs, strlen($extractAbs))), '/');
        if (! $launch) {
            $index = $this->findFileRecursive($extractAbs, 'index.html');
            $launch = $index ? ltrim(str_replace('\\', '/', substr($index, strlen($extractAbs))), '/') : null;
        }

        return [$version, $launch, $manifestRel ? $extractRel.'/'.$manifestRel : null];
    }

    protected function findFileRecursive(string $dir, string $basename): ?string
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (strcasecmp($file->getFilename(), $basename) === 0) {
                return $file->getPathname();
            }
        }

        return null;
    }

    public function deleteMaterial(LmsMaterial $material): void
    {
        if ($material->path) {
            Storage::disk($material->disk ?: 'public')->delete($material->path);
        }
        $material->delete();
    }

    public function deleteScorm(LmsScormPackage $scorm): void
    {
        $scorm->loadMissing('material');
        if ($scorm->extract_path) {
            Storage::disk('public')->deleteDirectory($scorm->extract_path);
        }

        $material = $scorm->material;
        $scorm->delete();
        if ($material) {
            $this->deleteMaterial($material);
        }
    }
}
