<?php

namespace Modules\ExportTemplates\Enums;

enum OutputFormat: string
{
    case WORD = 'word';
    case EXCEL = 'excel';

    public static function fromExtension(?string $extension): ?self
    {
        return match (strtolower(trim((string) $extension, ". \t\n\r\0\x0B"))) {
            'doc', 'docx' => self::WORD,
            'xls', 'xlsx', 'xlsm', 'xlsb', 'csv' => self::EXCEL,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::WORD => 'Word',
            self::EXCEL => 'Excel',
        };
    }
}
