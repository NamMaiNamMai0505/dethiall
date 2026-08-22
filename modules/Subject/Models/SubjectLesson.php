<?php

namespace Modules\Subject\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubjectLesson extends Model
{
    use SoftDeletes;

    public const KIND_UNIT = 'unit';

    public const KIND_CHAPTER = 'chapter';

    public const KIND_LESSON = 'lesson';

    public const KIND_SUB = 'sub';

    public const KIND_EXAM = 'exam';

    protected $table = 'subject_lessons';

    protected $fillable = [
        'subject_id',
        'parent_id',
        'code',
        'name',
        'lesson_kind',
        'sort_order',
        'theory_hours',
        'practice_hours',
        'exam_hours',
        'total_hours',
        'semester',
        'description',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'theory_hours' => 'integer',
        'practice_hours' => 'integer',
        'exam_hours' => 'integer',
        'total_hours' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('code');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeUnits($query)
    {
        return $query->where('lesson_kind', self::KIND_UNIT);
    }

    public function getDisplayLabelAttribute(): string
    {
        $name = trim((string) ($this->name ?? ''));
        $code = trim((string) ($this->code ?? ''));

        if ($name !== '' && $code !== '' && ! str_contains(mb_strtolower($name), mb_strtolower($code))) {
            return $code.': '.$name;
        }

        return $name !== '' ? $name : ($code !== '' ? $code : 'Bài #'.$this->id);
    }

    public function isUnit(): bool
    {
        return $this->lesson_kind === self::KIND_UNIT
            || (bool) preg_match('/^unit\s*\d+/iu', (string) $this->name);
    }
}
