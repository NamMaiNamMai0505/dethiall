<?php

namespace Modules\Subject\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subjects';

    protected $fillable = [
        'name',
        'code',
        'faculty_code',
        'absence_limit_percent',
        'abbreviation',
        'color',
        'description',
        'specialization_id',
        'credits',
        'theory_hours',
        'practice_hours',
        'self_study_hours',
        'exam_hours',
        'review_hours',
        'level',
        'semester',
        'prerequisites',
        'assessment_method',
        'is_required',
        'is_special_activity',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'prerequisites' => 'array',
        'is_required' => 'boolean',
        'is_special_activity' => 'boolean',
        'is_active' => 'boolean',
        'credits' => 'integer',
        'theory_hours' => 'integer',
        'practice_hours' => 'integer',
        'self_study_hours' => 'integer',
        'exam_hours' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Define relationships

    /**
     * Get the specialization that this subject belongs to
     */
    /** Môn học thuộc 1 ngành đào tạo */
    public function specialization()
    {
        return $this->belongsTo(\Modules\Specialization\Models\Specialization::class);
    }

    /** Chi tiết môn học / danh sách bài học */
    public function lessons()
    {
        return $this->hasMany(SubjectLesson::class, 'subject_id')->orderBy('sort_order')->orderBy('code');
    }

    /**
     * Get the user who created this subject
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this subject
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // Scopes

    /**
     * Scope to get only active subjects
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Khoa phụ trách môn học. Ưu tiên giá trị đã phân công tường minh (cột
     * faculty_code thật); môn chưa được gán mới suy ra từ hậu tố mã môn
     * (K1–K8) như trước khi có cột này — dự phòng cho dữ liệu cũ / import.
     * Sprint 44 §2.2: mã môn có thể đổi theo quy ước khác nên không còn là
     * nguồn phân công chính.
     */
    public function getFacultyCodeAttribute(): ?string
    {
        $explicit = $this->attributes['faculty_code'] ?? null;
        if ($explicit) {
            return $explicit;
        }

        return \Modules\Subject\Support\SubjectCodePrefix::facultyCodeFromSubjectCode($this->code ?? '');
    }

    /**
     * Lọc môn thuộc khoa của user (manager khoa K1–K8).
     */
    public function scopeForFacultyManager($query, $user = null)
    {
        return \App\Support\TrainingDept::applySubjectFacultyScope($query, $user);
    }

    /**
     * Scope to get only required subjects
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope to get only elective subjects
     */
    public function scopeElective($query)
    {
        return $query->where('is_required', false);
    }

    /**
     * Scope to search by name or code
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('abbreviation', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Sinh viết tắt từ chữ cái đầu mỗi từ (vd: "Thuốc thông thường" → "TTT").
     */
    public static function generateAbbreviationFromName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $abbr = '';

        foreach ($words as $word) {
            // Bỏ ký tự không phải chữ/số ở đầu từ (ngoặc, dấu…)
            $word = preg_replace('/^[^\p{L}\p{N}]+/u', '', $word) ?? $word;
            if ($word === '') {
                continue;
            }
            if (preg_match('/^./u', $word, $m)) {
                $abbr .= mb_strtoupper($m[0], 'UTF-8');
            }
        }

        // Giới hạn độ dài hợp lý cho xuất lịch
        if (mb_strlen($abbr) > 50) {
            $abbr = mb_substr($abbr, 0, 50);
        }

        return $abbr;
    }

    /**
     * Mã hiển thị (ẩn prefix B_CDDD_ / A_xxx_). DB vẫn giữ full code.
     */
    public function getDisplayCodeAttribute(): string
    {
        return \Modules\Subject\Support\SubjectCodePrefix::displayCode($this->code ?? '');
    }

    /**
     * Tên hiển thị ngắn khi xuất lịch:
     * viết tắt đã lưu → tự sinh từ tên → mã hiển thị → tên đầy đủ.
     */
    public function getShortLabelAttribute(): string
    {
        $abbr = trim((string) ($this->abbreviation ?? ''));
        if ($abbr !== '') {
            return $abbr;
        }

        $fromName = self::generateAbbreviationFromName($this->name ?? '');
        if ($fromName !== '') {
            return $fromName;
        }

        $code = $this->display_code;
        if ($code !== '') {
            return $code;
        }

        return (string) ($this->name ?? '');
    }

    /**
     * Màu nhận diện môn (#RRGGBB). Tự sinh ổn định từ id/tên nếu chưa gán.
     */
    public function getDisplayColorAttribute(): string
    {
        $color = trim((string) ($this->color ?? ''));
        if (preg_match('/^#?[0-9A-Fa-f]{6}$/', $color)) {
            return str_starts_with($color, '#') ? strtoupper($color) : '#'.strtoupper($color);
        }

        return self::defaultColorForKey((string) ($this->id ?: $this->name ?: 'subject'));
    }

    public static function defaultColorForKey(string $key): string
    {
        $palette = [
            '#4EA1FF', '#358FEE', '#00B050', '#0070C0', '#C00000',
            '#7030A0', '#ED7D31', '#00B0F0', '#FFC000', '#70AD47',
            '#5B9BD5', '#A9D08E', '#F4B183', '#9DC3E6', '#C65911',
        ];
        $idx = abs(crc32($key)) % count($palette);

        return $palette[$idx];
    }

    public static function normalizeColor(?string $color): ?string
    {
        $color = trim((string) $color);
        if ($color === '') {
            return null;
        }
        if (preg_match('/^#?([0-9A-Fa-f]{6})$/', $color, $m)) {
            return '#'.strtoupper($m[1]);
        }

        return null;
    }

    /**
     * Scope to filter by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to filter by specialization
     */
    public function scopeBySpecialization($query, $specializationId)
    {
        return $query->where('specialization_id', $specializationId);
    }

    /**
     * Môn của ngành/học kỳ đang xếp cùng các hoạt động lịch dùng chung.
     */
    public function scopeForTrainingSchedule($query, int $specializationId, ?string $semester = null)
    {
        return $query->where(function ($query) use ($specializationId, $semester) {
            $query->where('is_special_activity', true)
                ->orWhere(function ($regular) use ($specializationId, $semester) {
                    $regular->where('specialization_id', $specializationId)
                        ->when(
                            $semester,
                            fn ($builder) => $builder->where('semester', $semester)
                        );
                });
        });
    }

    /**
     * Scope to filter by assessment method
     */
    public function scopeByAssessmentMethod($query, $method)
    {
        return $query->where('assessment_method', $method);
    }

    /**
     * Scope to filter by semester
     */
    public function scopeBySemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    // Accessors & Mutators

    /**
     * Get the level in Vietnamese
     */
    public function getLevelTextAttribute()
    {
        return match ($this->level) {
            'basic' => 'Cơ bản',
            'intermediate' => 'Trung cấp',
            'advanced' => 'Nâng cao',
            default => 'Không xác định'
        };
    }

    /**
     * Get the assessment method in Vietnamese
     */
    public function getAssessmentMethodTextAttribute()
    {
        return match ($this->assessment_method) {
            'exam' => 'Thi viết',
            'multiple_choice_questions' => 'Thi trắc nghiệm',
            'assignment' => 'Bài tập',
            'project' => 'Đồ án',
            'combined' => 'Tổng hợp',
            default => 'Không xác định'
        };
    }

    /**
     * Get the semester in Vietnamese
     */
    public function getSemesterTextAttribute()
    {
        $semesters = [
            'semester_1' => 'Học kỳ 1',
            'semester_2' => 'Học kỳ 2',
            'semester_3' => 'Học kỳ 3',
            'semester_4' => 'Học kỳ 4',
            'semester_5' => 'Học kỳ 5',
            'semester_6' => 'Học kỳ 6',
            'summer' => 'Học kỳ hè',
        ];

        return $semesters[$this->semester] ?? 'Không xác định';
    }

    /**
     * Get the subject type (required/elective) in Vietnamese
     */
    public function getSubjectTypeTextAttribute()
    {
        return $this->is_required ? 'Bắt buộc' : 'Tự chọn';
    }

    /**
     * Get the status text
     */
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Đang hoạt động' : 'Tạm ngưng';
    }

    /**
     * Get the status color for UI
     */
    public function getStatusColorAttribute()
    {
        return $this->is_active ? 'success' : 'danger';
    }

    /**
     * Get the subject type color for UI
     */
    public function getSubjectTypeColorAttribute()
    {
        return $this->is_required ? 'blue' : 'green';
    }

    /**
     * Get the level color for UI
     */
    public function getLevelColorAttribute()
    {
        return match ($this->level) {
            'basic' => 'green',
            'intermediate' => 'yellow',
            'advanced' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get total hours
     */
    public function getTotalHoursAttribute()
    {
        return $this->theory_hours + $this->practice_hours + $this->self_study_hours + $this->exam_hours;
    }

    /**
     * Get formatted hours display
     */
    public function getHoursDisplayAttribute()
    {
        $parts = [];

        if ($this->theory_hours > 0) {
            $parts[] = "LT: {$this->theory_hours}h";
        }

        if ($this->practice_hours > 0) {
            $parts[] = "TH: {$this->practice_hours}h";
        }

        if ($this->self_study_hours > 0) {
            $parts[] = "Tự ôn: {$this->self_study_hours}h";
        }

        if ($this->exam_hours > 0) {
            $parts[] = "Thi/kiểm tra: {$this->exam_hours}h";
        }

        return implode(' | ', $parts) ?: 'Chưa xác định';
    }

    /** Lịch chi tiết */
    public function scheduleDetails()
    {
        return $this->hasMany(\Modules\ScheduleDetail\Models\ScheduleDetail::class, 'subject_id');
    }

    /**
     * Lấy số tiết đã phân bổ theo loại
     */
    public function getTheoryDoneAttribute(): int
    {
        return $this->scheduleDetails()->where('lesson_type', 'theory')->count();
    }

    public function getPracticeDoneAttribute(): int
    {
        return $this->scheduleDetails()->where('lesson_type', 'practice')->count();
    }

    public function getSelfStudyDoneAttribute(): int
    {
        return $this->scheduleDetails()->where('lesson_type', 'self_study')->count();
    }

    public function getExamDoneAttribute(): int
    {
        return $this->scheduleDetails()->where('lesson_type', 'final_exam')->count();
    }

    /**
     * Lấy % tiến độ theo từng loại (dùng cho progress bar)
     */
    public function getTheoryProgressAttribute(): float
    {
        return $this->theory_hours > 0
            ? round(($this->theory_done / $this->theory_hours) * 100, 1)
            : 0;
    }

    public function getPracticeProgressAttribute(): float
    {
        return $this->practice_hours > 0
            ? round(($this->practice_done / $this->practice_hours) * 100, 1)
            : 0;
    }

    public function getSelfStudyProgressAttribute(): float
    {
        return $this->self_study_hours > 0
            ? round(($this->self_study_done / $this->self_study_hours) * 100, 1)
            : 0;
    }

    public function getExamProgressAttribute(): float
    {
        return $this->exam_hours > 0
            ? round(($this->exam_done / $this->exam_hours) * 100, 1)
            : 0;
    }

    /** Môn học được nhiều GV assign */
    public function teachingAssignment()
    {
        return $this->hasMany(\Modules\TeachingAssignment\Models\TeachingAssignment::class);
    }

    /** Truy cập nhanh: môn học có nhiều GV */
    public function instructors()
    {
        return $this->belongsToMany(
            \Modules\Instructor\Models\Instructor::class,
            'teaching_assignment',
            'subject_id',
            'instructor_id'
        )->withTimestamps();
    }

    /**
     * Lấy remaining hours cho lesson_type trong training schedule cụ thể
     *
     * @param  string  $lessonType
     * @param  int  $trainingScheduleId
     * @return int Remaining hours (0 nếu đã hết hoặc invalid)
     */
    public function getRemainingHoursForType($lessonType, $trainingScheduleId)
    {
        // Map lesson_type với field hours
        $totalHours = match ($lessonType) {
            'theory' => $this->theory_hours ?? 0,
            'practice' => $this->practice_hours ?? 0,
            'self_study' => $this->self_study_hours ?? 0,
            'final_exam' => $this->exam_hours ?? 0,
            default => 0 // Không check cho other
        };

        if ($totalHours === 0) {
            return 0; // Hoặc return PHP_INT_MAX nếu cho phép unlimited – decide sau
        }

        // Count existing ScheduleDetail cho type này trong TS
        $usedHours = \Modules\ScheduleDetail\Models\ScheduleDetail::where('subject_id', $this->id)
            ->where('training_schedule_id', $trainingScheduleId)
            ->where('lesson_type', $lessonType)
            ->count(); // Giả sử 1 detail = 1 hour. Nếu period multi, dùng ->sum('period_hours') nếu add field

        return max(0, $totalHours - $usedHours);
    }
}

/*
đếm số h còn lại của môn học đó trong lịch cụ thể: ví dụ Traning schedule của Y54(id =11), môn điều dưỡng cơ bản(id = 1)
$subject = \Modules\Subject\Models\Subject::find(1); // id subject
echo $subject->getRemainingHoursForType('theory', 11); // id của traning schedule

đếm số tiết đã sử dụng của 1 môn học trong lịch cụ thể:
$count = \Modules\ScheduleDetail\Models\ScheduleDetail::hourUsage(1, 11, 'theory', null)->first()->usage_count ?? 0; // Y54(id =11), môn điều dưỡng cơ bản(id = 1)
echo $count; // Nên = số details matching
*/
