<?php

namespace Modules\ExportTemplates\Services;

use Modules\ExportTemplates\Contracts\DocumentConverterInterface;
use Symfony\Component\Process\Process;

class LibreOfficeDocumentConverter implements DocumentConverterInterface
{
    public function supports(string $sourceExtension, string $targetExtension): bool
    {
        return in_array(strtolower(ltrim($sourceExtension, '.')), ['doc', 'docx', 'xls', 'xlsx', 'odt', 'ods'], true)
            && strtolower(ltrim($targetExtension, '.')) === 'pdf';
    }

    public function convert(string $sourcePath, string $targetExtension, ?string $destinationPath = null): string
    {
        $sourcePath = realpath($sourcePath) ?: '';
        $sourceExtension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $targetExtension = strtolower(ltrim($targetExtension, '.'));
        if ($sourcePath === '' || ! is_file($sourcePath) || ! $this->supports($sourceExtension, $targetExtension)) {
            throw new \InvalidArgumentException('Định dạng hoặc file nguồn không được hỗ trợ để chuyển PDF.');
        }

        $destinationIsFile = $destinationPath
            && ! is_dir($destinationPath)
            && pathinfo($destinationPath, PATHINFO_EXTENSION) !== '';
        $temporaryOutputDirectory = $destinationIsFile ? $this->temporaryOutputDirectory() : null;
        $outputDirectory = $temporaryOutputDirectory ?: match (true) {
            (bool) $destinationPath => $destinationPath,
            default => sys_get_temp_dir(),
        };
        if (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0700, true) && ! is_dir($outputDirectory)) {
            throw new \RuntimeException('Không thể tạo thư mục output cho PDF.');
        }

        try {
            $generated = $outputDirectory.DIRECTORY_SEPARATOR.pathinfo($sourcePath, PATHINFO_FILENAME).'.pdf';
            $process = null;
            // LibreOffice trên Windows đôi lúc trả “source file could not be loaded”
            // trong lần khởi động headless đầu tiên. Thử lại đúng một lần bằng
            // profile sạch khác; source và dữ liệu không bị render lại.
            foreach (range(1, 2) as $attempt) {
                $process = $this->runConversion($sourcePath, $outputDirectory);
                if (is_file($generated)) {
                    break;
                }
                if ($attempt === 1) {
                    usleep(300_000);
                }
            }
            if (! is_file($generated) && PHP_OS_FAMILY === 'Windows') {
                $process = $this->runShellConversion($sourcePath, $outputDirectory);
            }
            if (! is_file($generated) && $destinationIsFile && getenv('LIBREOFFICE_CHILD_CONVERSION') !== '1') {
                $process = $this->runPhpCliConversion($sourcePath, $destinationPath);
                if (is_file($destinationPath)) {
                    return $destinationPath;
                }
            }

            if (! $process->isSuccessful() && ! is_file($generated)) {
                $detail = trim(implode("\n", array_filter([
                    'Mã thoát: '.($process->getExitCode() ?? 'không xác định'),
                    trim($process->getErrorOutput()),
                    trim($process->getOutput()),
                ])));

                throw new \RuntimeException('LibreOffice không thể chuyển file sang PDF: '.$detail);
            }
            if (! is_file($generated)) {
                throw new \RuntimeException('LibreOffice không tạo được file PDF output.');
            }
            if ($destinationIsFile) {
                $destinationDirectory = dirname($destinationPath);
                if (! is_dir($destinationDirectory) && ! mkdir($destinationDirectory, 0700, true) && ! is_dir($destinationDirectory)) {
                    throw new \RuntimeException('Không thể tạo thư mục lưu PDF output.');
                }
                @unlink($destinationPath);
                if (! @copy($generated, $destinationPath)) {
                    throw new \RuntimeException('Không thể lưu file PDF output.');
                }

                return $destinationPath;
            }

            return $generated;
        } finally {
            if ($temporaryOutputDirectory) {
                $this->removeTemporaryOutputDirectory($temporaryOutputDirectory);
            }
        }
    }

    private function runConversion(string $sourcePath, string $outputDirectory): Process
    {
        $profileDirectory = $this->temporaryProfileDirectory();
        try {
            $process = new Process([
                $this->resolveBinary(),
                '-env:UserInstallation='.$this->fileUri($profileDirectory),
                '--headless', '--nologo', '--nodefault', '--nofirststartwizard',
                '--convert-to', 'pdf', '--outdir', $outputDirectory, $sourcePath,
            ]);
            $process->setWorkingDirectory(sys_get_temp_dir());
            $process->setTimeout((float) config('export_templates.converter.timeout', 120));
            $process->run();

            return $process;
        } finally {
            $this->removeTemporaryProfile($profileDirectory);
        }
    }

    private function runShellConversion(string $sourcePath, string $outputDirectory): Process
    {
        $profileDirectory = $this->temporaryProfileDirectory();
        try {
            $command = implode(' ', [
                'call',
                $this->windowsQuote($this->resolveBinary()),
                $this->windowsQuote('-env:UserInstallation='.$this->fileUri($profileDirectory)),
                '--headless',
                '--nologo',
                '--nodefault',
                '--nofirststartwizard',
                '--convert-to',
                'pdf',
                '--outdir',
                $this->windowsQuote($outputDirectory),
                $this->windowsQuote($sourcePath),
            ]);
            $process = new Process(['cmd.exe', '/d', '/c', $command]);
            $process->setWorkingDirectory(dirname($sourcePath));
            $process->setTimeout((float) config('export_templates.converter.timeout', 120));
            $process->run();

            return $process;
        } finally {
            $this->removeTemporaryProfile($profileDirectory);
        }
    }

    private function runPhpCliConversion(string $sourcePath, string $destinationPath): Process
    {
        $processTemp = storage_path('app/private/process-temp');
        if (! is_dir($processTemp) && ! mkdir($processTemp, 0700, true) && ! is_dir($processTemp)) {
            throw new \RuntimeException('Không thể tạo thư mục tạm cho tiến trình chuyển PDF.');
        }

        $process = new Process([
            PHP_BINARY,
            base_path('artisan'),
            'documents:convert-pdf',
            $sourcePath,
            $destinationPath,
        ]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout((float) config('export_templates.converter.timeout', 120));
        $environment = is_array(getenv()) ? getenv() : [];
        $environment['LIBREOFFICE_CHILD_CONVERSION'] = '1';
        $environment['TEMP'] = $processTemp;
        $environment['TMP'] = $processTemp;
        $process->setEnv($environment);
        $process->run();

        return $process;
    }

    /**
     * Trên Windows soffice.exe là GUI launcher và có thể trả mã thoát trước
     * khi chuyển xong. soffice.com là console launcher đồng bộ dành cho CLI.
     */
    private function resolveBinary(): string
    {
        $binary = (string) config('export_templates.converter.binary', 'soffice');
        if (PHP_OS_FAMILY === 'Windows' && preg_match('/\.exe$/i', $binary)) {
            $consoleBinary = preg_replace('/\.exe$/i', '.com', $binary);
            if (is_string($consoleBinary) && is_file($consoleBinary)) {
                return $consoleBinary;
            }
        }

        return $binary;
    }

    private function temporaryProfileDirectory(): string
    {
        $root = storage_path('app/private/libreoffice-profiles');
        if (! is_dir($root) && ! mkdir($root, 0700, true) && ! is_dir($root)) {
            throw new \RuntimeException('Không thể tạo thư mục profile tạm cho LibreOffice.');
        }

        $directory = $root.DIRECTORY_SEPARATOR.'lms-soffice-'.bin2hex(random_bytes(8));
        if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Không thể tạo profile tạm cho LibreOffice.');
        }

        return $directory;
    }

    private function temporaryOutputDirectory(): string
    {
        $root = storage_path('app/private/libreoffice-conversions');
        if (! is_dir($root) && ! mkdir($root, 0700, true) && ! is_dir($root)) {
            throw new \RuntimeException('Không thể tạo thư mục tạm cho LibreOffice.');
        }

        $directory = $root.DIRECTORY_SEPARATOR.'lms-convert-'.bin2hex(random_bytes(8));
        if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Không thể tạo thư mục output tạm cho LibreOffice.');
        }

        return $directory;
    }

    private function fileUri(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $prefix = PHP_OS_FAMILY === 'Windows' ? 'file:///' : 'file://';

        return $prefix.str_replace(' ', '%20', $normalized);
    }

    private function windowsQuote(string $value): string
    {
        return '"'.str_replace('"', '""', $value).'"';
    }

    private function removeTemporaryProfile(string $directory): void
    {
        $tempRoot = realpath(storage_path('app/private/libreoffice-profiles'));
        $target = realpath($directory);
        if ($tempRoot === false || $target === false
            || ! str_starts_with($target, $tempRoot.DIRECTORY_SEPARATOR)
            || ! str_starts_with(basename($target), 'lms-soffice-')) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($target);
    }

    private function removeTemporaryOutputDirectory(string $directory): void
    {
        $root = realpath(storage_path('app/private/libreoffice-conversions'));
        $target = realpath($directory);
        if ($root === false || $target === false
            || ! str_starts_with($target, $root.DIRECTORY_SEPARATOR)
            || ! str_starts_with(basename($target), 'lms-convert-')) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($target);
    }
}
