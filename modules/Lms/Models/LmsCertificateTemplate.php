<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsCertificateTemplate extends Model
{
    protected $table = 'lms_certificate_templates';

    protected $fillable = [
        'lms_course_id', 'title', 'body_html', 'layout_json', 'issuer_name',
        'min_score', 'min_progress_pct', 'require_survey', 'is_active', 'created_by',
    ];

    protected $casts = [
        'min_score' => 'float',
        'min_progress_pct' => 'float',
        'require_survey' => 'boolean',
        'is_active' => 'boolean',
        'layout_json' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(LmsCertificate::class, 'lms_certificate_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
