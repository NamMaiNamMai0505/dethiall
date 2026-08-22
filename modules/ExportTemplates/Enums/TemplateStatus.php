<?php

namespace Modules\ExportTemplates\Enums;

enum TemplateStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case INVALID = 'invalid';

    public function canBeActivated(): bool
    {
        return in_array($this, [self::DRAFT, self::PUBLISHED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Bản nháp',
            self::PUBLISHED => 'Đã phát hành',
            self::ARCHIVED => 'Đã lưu trữ',
            self::INVALID => 'Không hợp lệ',
        };
    }
}
