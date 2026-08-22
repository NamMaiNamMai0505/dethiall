<?php

namespace Modules\Subject\Support;

final class SpecialSubjectCatalog
{
    public const DESCRIPTION_MARKER = '[SYSTEM:SPECIAL_SCHEDULE_ACTIVITY]';

    /**
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            'VHTT' => 'Ngày chính trị văn hóa tinh thần',
            'NPL' => 'Ngày pháp luật',
            'SHL' => 'Sinh hoạt lớp',
            'NL' => 'Nghỉ lễ',
            'NT' => 'Nghỉ tết',
            'NH' => 'Nghỉ hè',
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::definitions());
    }

    public static function contains(?string $code): bool
    {
        return array_key_exists(
            mb_strtoupper(trim((string) $code), 'UTF-8'),
            self::definitions()
        );
    }
}
