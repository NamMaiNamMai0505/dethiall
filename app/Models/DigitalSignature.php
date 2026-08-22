<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DigitalSignature extends Model
{
    protected $fillable = [
        'user_id',
        'slot_key',
        'display_name',
        'role_line1',
        'role_line2',
        'image_path',
        'match_names',
        'is_system_template',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'match_names' => 'array',
        'is_system_template' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const SLOT_NGUOI_LAM_LICH = 'nguoi_lam_lich';

    public const SLOT_KT_TRUONG_PHONG = 'kt_truong_phong';

    public const SLOT_KT_HIEU_TRUONG = 'kt_hieu_truong';

    public const SLOT_CUSTOM = 'custom';

    public static function systemSlots(): array
    {
        return [
            self::SLOT_NGUOI_LAM_LICH => 'Người làm lịch',
            self::SLOT_KT_TRUONG_PHONG => 'KT. Trưởng phòng',
            self::SLOT_KT_HIEU_TRUONG => 'KT. Hiệu trưởng',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwnedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (int) $this->user_id === (int) $user->id;
    }

    public function canManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->isOwnedBy($user);
    }

    /** URL public xem ảnh (storage link). */
    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }
        // public/images/... hoặc storage path
        if (str_starts_with($this->image_path, 'images/')) {
            return asset($this->image_path);
        }
        if (Storage::disk('public')->exists($this->image_path)) {
            return Storage::disk('public')->url($this->image_path);
        }
        if (is_file(public_path($this->image_path))) {
            return asset($this->image_path);
        }
        if (is_file(public_path('images/'.$this->image_path))) {
            return asset('images/'.$this->image_path);
        }

        return null;
    }

    /** Absolute path cho PhpSpreadsheet / PhpWord. */
    public function absoluteImagePath(): ?string
    {
        if (! $this->image_path) {
            return null;
        }
        $candidates = [
            storage_path('app/public/'.$this->image_path),
            public_path($this->image_path),
            public_path('images/'.$this->image_path),
            public_path('images/signatures/lhl/'.basename($this->image_path)),
            $this->image_path,
        ];
        foreach ($candidates as $p) {
            if (is_string($p) && is_file($p)) {
                return $p;
            }
        }

        return null;
    }

    public function slotLabel(): string
    {
        return self::systemSlots()[$this->slot_key] ?? ($this->slot_key === self::SLOT_CUSTOM ? 'Tuỳ chọn' : $this->slot_key);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystemTemplates($query)
    {
        return $query->where('is_system_template', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
