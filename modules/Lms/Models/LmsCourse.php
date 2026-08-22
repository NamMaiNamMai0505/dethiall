<?php

namespace Modules\Lms\Models;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Class\Models\ClassModel;
use Modules\Grades\Models\GradeBook;
use Modules\Instructor\Models\Instructor;
use Modules\Subject\Models\Subject;

class LmsCourse extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'lms_courses';

    protected $fillable = [
        'code',
        'section_code',
        'title',
        'description',
        'subject_id',
        'class_id',
        'academic_year_id',
        'term',
        'source_type',
        'source_id',
        'provision_key',
        'instructor_id',
        'status',
        'chat_locked',
        'is_standalone',
        'starts_at',
        'ends_at',
        'content_synced_at',
        'roster_synced_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_standalone' => 'boolean',
        'chat_locked' => 'boolean',
        'content_synced_at' => 'datetime',
        'roster_synced_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(
            Instructor::class,
            'lms_course_instructors',
            'lms_course_id',
            'instructor_id'
        )->withPivot(['role', 'source'])->withTimestamps();
    }

    public function courseInstructors(): HasMany
    {
        return $this->hasMany(LmsCourseInstructor::class, 'lms_course_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(LmsCourseMember::class, 'lms_course_id')
            ->where('status', LmsCourseMember::STATUS_ACTIVE);
    }

    public function allMembers(): HasMany
    {
        return $this->hasMany(LmsCourseMember::class, 'lms_course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LmsLesson::class, 'lms_course_id')->orderBy('sort_order')->orderBy('id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LmsMaterial::class, 'lms_course_id')->orderBy('sort_order')->orderByDesc('id');
    }

    public function scormPackages(): HasMany
    {
        return $this->hasMany(LmsScormPackage::class, 'lms_course_id')->orderByDesc('id');
    }

    public function forumTopics(): HasMany
    {
        return $this->hasMany(LmsForumTopic::class, 'lms_course_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(LmsChatMessage::class, 'lms_course_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LmsAssignment::class, 'lms_course_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(LmsExam::class, 'lms_course_id');
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(LmsAttendanceSession::class, 'lms_course_id');
    }

    public function progressSummaries(): HasMany
    {
        return $this->hasMany(LmsProgressSummary::class, 'lms_course_id');
    }

    public function learningAlerts(): HasMany
    {
        return $this->hasMany(LmsLearningAlert::class, 'lms_course_id');
    }

    public function gradebookRows(): HasMany
    {
        return $this->hasMany(LmsGradebookRow::class, 'lms_course_id');
    }

    public function officialGradeBook(): HasOne
    {
        return $this->hasOne(GradeBook::class, 'lms_course_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'Đang mở',
            self::STATUS_ARCHIVED => 'Lưu trữ',
            default => 'Nháp',
        };
    }
}
