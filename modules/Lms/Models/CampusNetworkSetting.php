<?php

namespace Modules\Lms\Models;

use Illuminate\Database\Eloquent\Model;

class CampusNetworkSetting extends Model
{
    protected $table = 'campus_network_settings';

    protected $fillable = [
        'name', 'wifi_mac', 'ip_cidrs', 'probe_url',
        'require_campus_network', 'is_active', 'sort_order', 'note',
    ];

    protected $casts = [
        'require_campus_network' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** @return list<string> */
    public function cidrList(): array
    {
        $raw = (string) ($this->ip_cidrs ?? '');
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }

    public static function normalizeMac(?string $mac): ?string
    {
        if (! $mac) {
            return null;
        }
        $hex = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac) ?? '');
        if (strlen($hex) !== 12) {
            return strtoupper(trim($mac));
        }

        return implode(':', str_split($hex, 2));
    }
}
