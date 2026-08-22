<?php

namespace Modules\Grades\Models;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsCourse;
use Modules\Subject\Models\Subject;

class GradeBook extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'open';

    public const STATUS_LOCKED_GV = 'locked_gv';

    public const STATUS_PENDING_PDOT = 'pending_pdot';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REVISION = 'revision';

    protected $table = 'grade_books';

    protected $fillable = [
        'class_id', 'subject_id', 'instructor_id', 'lms_course_id',
        'academic_year', 'academic_year_id', 'title', 'status', 'locked_by', 'locked_at',
        'approved_by', 'approved_at', 'note', 'created_by',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function lmsCourse(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(GradeColumn::class, 'grade_book_id')->orderBy('sort_order');
    }

    public function cells(): HasMany
    {
        return $this->hasMany(GradeCell::class, 'grade_book_id');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(GradeChangeRequest::class, 'grade_book_id');
    }

    public function isEditableByInstructor(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_OPEN, self::STATUS_REVISION], true);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [
            self::STATUS_LOCKED_GV,
            self::STATUS_PENDING_PDOT,
            self::STATUS_APPROVED,
        ], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_OPEN => 'Đang nhập',
            self::STATUS_LOCKED_GV => 'GV đã khóa',
            self::STATUS_PENDING_PDOT => 'Chờ PDOT duyệt',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_REVISION => 'Cho phép sửa lại',
            default => $this->status,
        };
    }
}
