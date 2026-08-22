<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsAttendanceRecord extends Model
{
    protected $table = 'lms_attendance_records';

    public const STATUSES = ['present', 'absent', 'late', 'excused'];

    protected $fillable = [
        'lms_attendance_session_id', 'user_id', 'status', 'method',
        'checkin_lat', 'checkin_lng', 'checkin_accuracy_m', 'distance_m',
        'client_ip', 'network_ok', 'network_note', 'probe_ok', 'probe_note', 'gps_ok',
        'checked_in_at', 'note', 'marked_by',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checkin_lat' => 'float',
        'checkin_lng' => 'float',
        'checkin_accuracy_m' => 'integer',
        'distance_m' => 'integer',
        'network_ok' => 'boolean',
        'probe_ok' => 'boolean',
        'gps_ok' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LmsAttendanceSession::class, 'lms_attendance_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'present' => 'Có mặt',
            'absent' => 'Vắng',
            'late' => 'Muộn',
            'excused' => 'Có phép',
            default => $this->status,
        };
    }
}
