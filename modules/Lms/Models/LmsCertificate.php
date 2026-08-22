<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LmsCertificate extends Model
{
    protected $table = 'lms_certificates';

    protected $fillable = [
        'lms_course_id', 'user_id', 'lms_certificate_template_id', 'code', 'title',
        'final_score', 'progress_pct', 'issued_at', 'issued_by', 'status', 'meta',
    ];

    protected $casts = [
        'final_score' => 'float',
        'progress_pct' => 'float',
        'issued_at' => 'datetime',
        'meta' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LmsCertificateTemplate::class, 'lms_certificate_template_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public static function makeCode(): string
    {
        return 'CDHC2-'.strtoupper(Str::random(4)).'-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }
}
