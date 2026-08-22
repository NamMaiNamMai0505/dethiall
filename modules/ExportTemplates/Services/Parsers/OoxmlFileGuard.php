<?php

namespace Modules\ExportTemplates\Services\Parsers;

use Modules\ExportTemplates\Exceptions\InvalidTemplateException;

class OoxmlFileGuard
{
    private const MAX_FILE_BYTES = 10 * 1024 * 1024;

    private const MAX_UNCOMPRESSED_BYTES = 100 * 1024 * 1024;

    private const MAX_ENTRY_BYTES = 25 * 1024 * 1024;

    private const MAX_ENTRIES = 5000;

    public function assertSafe(string $absolutePath, string $extension): void
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new InvalidTemplateException(
                'Không thể đọc file template.',
                ['File không tồn tại hoặc không có quyền đọc.']
            );
        }

        $size = filesize($absolutePath);
        if ($size === false || $size <= 0 || $size > self::MAX_FILE_BYTES) {
            throw new InvalidTemplateException(
                'Kích thước file template không hợp lệ.',
                ['File phải lớn hơn 0 byte và không vượt quá 10 MB.']
            );
        }

        if (! in_array(strtolower($extension), ['docx', 'xlsx', 'xlsm'], true)) {
            return;
        }

        $zip = new \ZipArchive;
        if ($zip->open($absolutePath) !== true) {
            throw new InvalidTemplateException(
                'File OOXML bị hỏng.',
                ['Không thể mở cấu trúc ZIP của file Word/Excel.']
            );
        }

        try {
            if ($zip->numFiles > self::MAX_ENTRIES) {
                throw new InvalidTemplateException(
                    'File OOXML có quá nhiều thành phần.',
                    ['Số lượng thành phần trong file vượt giới hạn an toàn.']
                );
            }

            $totalSize = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                $name = (string) ($stat['name'] ?? '');
                $entrySize = (int) ($stat['size'] ?? 0);

                if (
                    $name === ''
                    || str_contains(str_replace('\\', '/', $name), '../')
                    || str_starts_with($name, '/')
                ) {
                    throw new InvalidTemplateException(
                        'File OOXML chứa đường dẫn không an toàn.',
                        ["Thành phần [{$name}] không hợp lệ."]
                    );
                }

                if ($entrySize > self::MAX_ENTRY_BYTES) {
                    throw new InvalidTemplateException(
                        'File OOXML chứa thành phần quá lớn.',
                        ["Thành phần [{$name}] vượt giới hạn an toàn."]
                    );
                }

                $totalSize += $entrySize;
                if ($totalSize > self::MAX_UNCOMPRESSED_BYTES) {
                    throw new InvalidTemplateException(
                        'Dung lượng giải nén của file OOXML quá lớn.',
                        ['Tổng dung lượng giải nén vượt giới hạn an toàn.']
                    );
                }
            }
        } finally {
            $zip->close();
        }
    }
}
