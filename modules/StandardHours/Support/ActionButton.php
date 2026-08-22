<?php

namespace Modules\StandardHours\Support;

class ActionButton
{
    public static function classes(string $variant = 'primary'): string
    {
        $base = 'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors';

        return $base.' '.match ($variant) {
            'secondary' => 'bg-gray-500 hover:bg-gray-600 text-white',
            'success' => 'bg-green-600 hover:bg-green-700 text-white',
            'danger' => 'bg-red-600 hover:bg-red-700 text-white',
            'warning' => 'bg-amber-500 hover:bg-amber-600 text-white',
            default => 'bg-blue-600 hover:bg-blue-700 text-white',
        };
    }
}
