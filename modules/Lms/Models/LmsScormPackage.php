<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class LmsScormPackage extends Model
{
    use SoftDeletes;

    protected $table = 'lms_scorm_packages';

    protected $fillable = [
        'lms_course_id',
        'lms_material_id',
        'title',
        'version',
        'launch_path',
        'extract_path',
        'manifest_path',
        'meta',
        'is_published',
        'uploaded_by',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_published' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LmsMaterial::class, 'lms_material_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function launchUrl(): ?string
    {
        if (! $this->extract_path || ! $this->launch_path) {
            return null;
        }

        $rel = trim($this->extract_path, '/').'/'.ltrim($this->launch_path, '/');

        return Storage::disk('public')->url($rel);
    }
}
