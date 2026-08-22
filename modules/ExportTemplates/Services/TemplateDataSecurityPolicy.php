<?php

namespace Modules\ExportTemplates\Services;

class TemplateDataSecurityPolicy
{
    /**
     * Những trường này không bao giờ được đưa vào Data Explorer.
     *
     * @var list<string>
     */
    private const DENIED_SEGMENTS = [
        'password',
        'password_hash',
        'remember_token',
        'token',
        'secret',
        'api_key',
        'permission',
        'permissions',
        'role',
        'roles',
        'email',
        'phone',
        'ip',
        'ip_address',
        'gps',
        'latitude',
        'longitude',
        'network',
    ];

    public function isAllowed(string $dataKey): bool
    {
        $normalized = mb_strtolower(str_replace(['[]', '-', ' '], ['', '_', '_'], $dataKey));
        $segments = preg_split('/\.+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return count(array_intersect($segments, self::DENIED_SEGMENTS)) === 0;
    }

    public function assertAllowed(string $dataKey): void
    {
        if (! $this->isAllowed($dataKey)) {
            throw new \DomainException("Trường dữ liệu [{$dataKey}] không được phép dùng trong template.");
        }
    }
}
