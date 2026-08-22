<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class LmsMaterial extends Model
{
    use SoftDeletes;

    protected $table = 'lms_materials';

    protected $fillable = [
        'lms_course_id',
        'lms_lesson_id',
        'title',
        'description',
        'kind',
        'disk',
        'path',
        'original_name',
        'mime',
        'size_bytes',
        'is_published',
        'sort_order',
        'uploaded_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'size_bytes' => 'integer',
        'sort_order' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LmsLesson::class, 'lms_lesson_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            'pdf' => 'PDF',
            'slide' => 'Slide',
            'video' => 'Video',
            'image' => 'Hình ảnh',
            'archive' => 'Nén',
            'scorm' => 'SCORM',
            default => 'Tài liệu',
        };
    }

    public function humanSize(): string
    {
        $b = (int) $this->size_bytes;
        if ($b < 1024) {
            return $b.' B';
        }
        if ($b < 1048576) {
            return round($b / 1024, 1).' KB';
        }

        return round($b / 1048576, 1).' MB';
    }

    public function url(): ?string
    {
        if (! $this->path) {
            return null;
        }
        try {
            return Storage::disk($this->disk ?: 'public')->url($this->path);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
