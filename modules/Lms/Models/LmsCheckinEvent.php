<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsCheckinEvent extends Model
{
    protected $table = 'lms_checkin_events';

    protected $fillable = [
        'lms_course_id',
        'lms_attendance_session_id',
        'user_id',
        'ok',
        'reason',
        'client_ip',
        'network_ok',
        'probe_ok',
        'gps_ok',
        'distance_m',
        'lat',
        'lng',
        'note',
    ];

    protected $casts = [
        'ok' => 'boolean',
        'network_ok' => 'boolean',
        'probe_ok' => 'boolean',
        'gps_ok' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
        'distance_m' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LmsAttendanceSession::class, 'lms_attendance_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function logAttempt(array $payload): self
    {
        return self::query()->create([
            'lms_course_id' => $payload['lms_course_id'] ?? null,
            'lms_attendance_session_id' => $payload['lms_attendance_session_id'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
            'ok' => (bool) ($payload['ok'] ?? false),
            'reason' => $payload['reason'] ?? null,
            'client_ip' => $payload['client_ip'] ?? null,
            'network_ok' => $payload['network_ok'] ?? null,
            'probe_ok' => $payload['probe_ok'] ?? null,
            'gps_ok' => $payload['gps_ok'] ?? null,
            'distance_m' => $payload['distance_m'] ?? null,
            'lat' => $payload['lat'] ?? null,
            'lng' => $payload['lng'] ?? null,
            'note' => isset($payload['note']) ? mb_substr((string) $payload['note'], 0, 500) : null,
        ]);
    }
}
