<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

class SystemSettings
{
    public static function get(string $portal, string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('system_settings')) {
            return $default;
        }

        return SystemSetting::query()
            ->where('portal', $portal)
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    public static function put(string $portal, string $key, mixed $value, string $type = 'string'): void
    {
        SystemSetting::query()->updateOrCreate(
            ['portal' => $portal, 'key' => $key],
            ['value' => $value, 'type' => $type, 'updated_by' => auth()->id()]
        );
    }
}
