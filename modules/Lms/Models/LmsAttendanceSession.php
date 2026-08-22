<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\ScheduleDetail\Models\ScheduleDetail;

class LmsAttendanceSession extends Model
{
    protected $table = 'lms_attendance_sessions';

    public const DEFAULT_QR_TTL_MINUTES = 120;

    protected $fillable = [
        'lms_course_id', 'title', 'session_date', 'mode', 'status',
        'schedule_detail_id',
        'require_campus_wifi', 'require_gps', 'allow_gps_bypass',
        'open_from', 'open_until', 'checkin_token', 'token_expires_at', 'qr_ttl_minutes',
        'note', 'created_by',
    ];

    protected $casts = [
        'session_date' => 'date',
        'open_from' => 'datetime',
        'open_until' => 'datetime',
        'token_expires_at' => 'datetime',
        'require_campus_wifi' => 'boolean',
        'require_gps' => 'boolean',
        'allow_gps_bypass' => 'boolean',
    ];

    /** P2: bắt buộc GPS trong bán kính (mode gps hoặc cờ require_gps). */
    public function requiresGpsHard(): bool
    {
        return $this->mode === 'gps' || (bool) $this->require_gps;
    }

    /** P2: cho phép GPS trong campus cứu khi IP/probe fail. */
    public function allowsGpsBypass(): bool
    {
        return (bool) $this->allow_gps_bypass || $this->mode === 'gps';
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function scheduleDetail(): BelongsTo
    {
        return $this->belongsTo(ScheduleDetail::class, 'schedule_detail_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(LmsAttendanceRecord::class, 'lms_attendance_session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        // self/qr/gps: nếu status=open và là ngày session (hoặc hôm nay) → cho điểm danh cả ngày
        // (tránh lệch timezone open_from/open_until làm mất nút điểm danh)
        if (in_array($this->mode, ['gps', 'self', 'qr'], true) && $this->session_date) {
            $day = $this->session_date->toDateString();
            $today = now()->toDateString();
            if ($day === $today) {
                return true;
            }
            // Ngày tương lai đã mở: vẫn cho bấm (demo / check-in sớm trong khoảng)
            if ($day > $today && $this->open_until && now()->lte($this->open_until)) {
                return true;
            }
            if ($day < $today && $this->open_until && now()->lte($this->open_until)) {
                return true;
            }
        }

        if ($this->open_from && now()->lt($this->open_from)) {
            return false;
        }
        if ($this->open_until && now()->gt($this->open_until)) {
            return false;
        }

        return true;
    }

    /** Học viên có thể tự điểm danh (GPS/QR/self) khi còn cửa sổ. */
    public function allowsSelfCheckin(): bool
    {
        return in_array($this->mode, ['self', 'qr', 'gps'], true) && $this->isOpen() && ! $this->isTokenExpired();
    }

    /** P1: token QR/self đã hết hạn TTL chưa (null = không giới hạn theo TTL). */
    public function isTokenExpired(): bool
    {
        if (! $this->checkin_token) {
            return false;
        }
        if (! $this->token_expires_at) {
            return false;
        }

        return now()->greaterThan($this->token_expires_at);
    }

    public function isTokenValid(?string $token): bool
    {
        if (! $this->checkin_token) {
            return true;
        }
        if (! $token || ! hash_equals((string) $this->checkin_token, (string) $token)) {
            return false;
        }

        return ! $this->isTokenExpired();
    }

    /**
     * Sinh token mới + đặt/hết hạn TTL.
     *
     * @return array{token:string,token_expires_at:Carbon|null,qr_ttl_minutes:int}
     */
    public function rotateToken(?int $ttlMinutes = null): array
    {
        $ttl = $ttlMinutes ?? $this->qr_ttl_minutes ?? self::DEFAULT_QR_TTL_MINUTES;
        $ttl = max(5, min(24 * 60, (int) $ttl));
        $expires = now()->addMinutes($ttl);
        if ($this->open_until && $this->open_until->lt($expires)) {
            $expires = $this->open_until->copy();
        }

        $token = self::makeToken();
        $this->forceFill([
            'checkin_token' => $token,
            'qr_ttl_minutes' => $ttl,
            'token_expires_at' => $expires,
        ])->save();

        return [
            'token' => $token,
            'token_expires_at' => $this->token_expires_at,
            'qr_ttl_minutes' => $ttl,
        ];
    }

    /**
     * Gán token lần đầu khi tạo session (self/qr/gps).
     *
     * @param  \DateTimeInterface|string|null  $openUntil
     * @return array{checkin_token:?string,qr_ttl_minutes:?int,token_expires_at:?Carbon}
     */
    public static function initialTokenPayload(?string $mode, ?int $ttlMinutes = null, $openUntil = null): array
    {
        if (! in_array($mode, ['self', 'qr', 'gps'], true)) {
            return [
                'checkin_token' => null,
                'qr_ttl_minutes' => null,
                'token_expires_at' => null,
            ];
        }

        $ttl = max(5, min(24 * 60, (int) ($ttlMinutes ?? self::DEFAULT_QR_TTL_MINUTES)));
        $expires = now()->addMinutes($ttl);
        if ($openUntil) {
            $until = Carbon::parse($openUntil);
            if ($until->lt($expires)) {
                $expires = $until;
            }
        }

        return [
            'checkin_token' => self::makeToken(),
            'qr_ttl_minutes' => $ttl,
            'token_expires_at' => $expires,
        ];
    }

    public static function makeToken(): string
    {
        return Str::random(40);
    }
}
