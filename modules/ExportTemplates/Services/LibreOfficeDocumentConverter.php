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

        $outputDirectory = $destinationPath
            ? (is_dir($destinationPath) ? $destinationPath : dirname($destinationPath))
            : sys_get_temp_dir();
        if (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0700, true) && ! is_dir($outputDirectory)) {
            throw new \RuntimeException('Không thể tạo thư mục output cho PDF.');
        }

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
        if ($destinationPath && $destinationPath !== $generated) {
            if (! @rename($generated, $destinationPath)) {
                throw new \RuntimeException('Không thể di chuyển file PDF output.');
            }

            return $destinationPath;
        }

        return $generated;
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
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lms-soffice-'.bin2hex(random_bytes(8));
        if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Không thể tạo profile tạm cho LibreOffice.');
        }

        return $directory;
    }

    private function fileUri(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $prefix = PHP_OS_FAMILY === 'Windows' ? 'file:///' : 'file://';

        return $prefix.str_replace(' ', '%20', $normalized);
    }

    private function removeTemporaryProfile(string $directory): void
    {
        $tempRoot = realpath(sys_get_temp_dir());
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
}
