<?php

namespace Modules\ScheduleDetail\Models;

use Database\Factories\ScheduleDetailFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Classroom\Models\Classroom;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\StandardHours\Models\ConversionCategory;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;
use Modules\TrainingSchedule\Models\TrainingSchedule;

class ScheduleDetail extends Model
{
    use HasFactory;

    protected static function newFactory(): ScheduleDetailFactory
    {
        return ScheduleDetailFactory::new();
    }

    protected $fillable = [
        'training_schedule_id',
        'date',
        'period',
        'subject_id',
        'subject_lesson_id',
        'instructor_id',
        'classroom_id',
        'lesson_type',
        'standard_hours_conversion_category_id',
    ];

    /** Buổi học có môn học */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /** Bài học (phân bổ cấp Khoa) */
    public function subjectLesson()
    {
        return $this->belongsTo(SubjectLesson::class, 'subject_lesson_id');
    }

    /** Buổi học có giảng viên */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    /** Buổi học ở phòng học */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /** Buổi học thuộc lịch đào tạo */
    public function trainingSchedule()
    {
        return $this->belongsTo(TrainingSchedule::class);
    }

    public function lmsAttendanceSession()
    {
        return $this->hasOne(LmsAttendanceSession::class, 'schedule_detail_id');
    }

    public function standardHourCategory()
    {
        return $this->belongsTo(
            ConversionCategory::class,
            'standard_hours_conversion_category_id'
        );
    }

    public function getLessonTypeLabelAttribute()
    {
        $map = [
            'theory' => '📘 Lý thuyết',
            'practice' => '🔬 Thực hành',
            'self_study' => '📝 Tự học',
            'final_exam' => '🏆 Thi/kiểm tra',
        ];

        return $map[$this->lesson_type] ?? 'Chưa xác định ';
    }

    /**
     * Scope cho teacher busy: Query ScheduleDetail conflict cho date/period/instructor, exclude schedule cụ thể
     * Trả về query builder để chain ->first() hoặc ->exists()
     */
    public function scopeBusyTeacher($query, $date, $period, $instructorId, $excludeScheduleId = null)
    {
        $query->where('date', $date)
            ->where('period', $period)
            ->where('instructor_id', $instructorId);
        if ($excludeScheduleId) {
            $query->where('training_schedule_id', '!=', $excludeScheduleId);
        }

        return $query;
    }

    /**
     * Scope cho classroom busy: Tương tự teacher
     */
    public function scopeBusyClassroom($query, $date, $period, $classroomId, $excludeScheduleId = null)
    {
        $query->where('date', $date)
            ->where('period', $period)
            ->where('classroom_id', $classroomId);
        if ($excludeScheduleId) {
            $query->where('training_schedule_id', '!=', $excludeScheduleId);
        }

        return $query;
    }

    /**
     * Scope để lấy count usage cho lesson_type trong training schedule (exclude nếu edit)
     * Trả về query với select COUNT(*) as usage_count
     *
     * @param  int  $subjectId
     * @param  int  $trainingScheduleId
     * @param  string  $lessonType
     * @param  int|null  $excludeId  ID để exclude khi edit
     * @return Builder
     */
    public function scopeHourUsage($query, $subjectId, $trainingScheduleId, $lessonType, $excludeId = null)
    {
        $query->where('subject_id', $subjectId)
            ->where('training_schedule_id', $trainingScheduleId)
            ->where('lesson_type', $lessonType);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->selectRaw('COUNT(*) as usage_count');
    }
}
