<?php

namespace Modules\TrainingSchedule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;

class TrainingSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'training_schedules';

    protected $fillable = [
        'name',
        'code',
        'abbreviation',
        'specialization_id',
        'class_id',
        'class_code',
        'classroom_id',
        'academic_year',
        'semester',
        'start_date',
        'end_date',
        'weekly_schedule',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'weekly_schedule' => 'array',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'class_code' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Define relationships

    public function scheduleDetails()
    {
        return $this->hasMany(\Modules\ScheduleDetail\Models\ScheduleDetail::class);
    }

    /**
     * Method: Kiểm tra xem có ScheduleDetail con không (trả về boolean).
     * Dùng cho single instance (như edit/show).
     */
    public function hasScheduleDetails(): bool
    {
        return $this->scheduleDetails()->exists(); // Query exists() để check mà không load full data (hiệu suất tốt).
    }

    /**
     * Optional: Local Scope - Để query nhiều TrainingSchedules có/không có details.
     * Ví dụ: TrainingSchedule::hasDetails()->get(); // Lấy tất cả schedules có details.
     */
    public function scopeHasDetails($query)
    {
        return $query->whereHas('scheduleDetails'); // whereHas() check relationship exists.
    }

    public function scopeNoDetails($query)
    {
        return $query->whereDoesntHave('scheduleDetails'); // Không có details.
    }

    /**
     * Get the specialization for this training schedule
     */
    public function specialization()
    {
        return $this->belongsTo(\Modules\Specialization\Models\Specialization::class, 'specialization_id');
    }

    /**
     * Get the class for this training schedule (using class_id)
     */
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the class for this training schedule (using class_code - legacy)
     */
    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_code', 'code');
    }

    /**
     * Get the user who created this training schedule
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this training schedule
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get the classroom for this training schedule
     */
    public function classroom()
    {
        return $this->belongsTo(\Modules\Classroom\Models\Classroom::class, 'classroom_id');
    }

    // Scopes

    /**
     * Scope to get only active training schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to search by name or code
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%");
        });
    }

    /**
     * Scope to filter by specialization
     */
    public function scopeBySpecialization($query, $specializationId)
    {
        return $query->where('specialization_id', $specializationId);
    }

    /**
     * Scope to filter by academic year
     */
    public function scopeByAcademicYear($query, $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

    /**
     * Scope to filter by semester
     */
    public function scopeBySemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    /**
     * Scope to get schedules within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($subQ) use ($startDate, $endDate) {
                    $subQ->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    // Accessors & Mutators

    /**
     * Get the semester in Vietnamese
     */
    public function getSemesterTextAttribute()
    {
        $semesters = [
            'semester_1' => 'Học kỳ 1',
            'semester_2' => 'Học kỳ 2',
            'summer' => 'Học kỳ hè',
        ];

        return $semesters[$this->semester] ?? $this->semester;
    }

    /**
     * Get formatted duration
     */
    public function getDurationTextAttribute()
    {
        try {
            if (! $this->start_date || ! $this->end_date) {
                return 'Chưa xác định';
            }

            // Ensure dates are Carbon instances
            $startDate = is_string($this->start_date) ? \Carbon\Carbon::parse($this->start_date) : $this->start_date;
            $endDate = is_string($this->end_date) ? \Carbon\Carbon::parse($this->end_date) : $this->end_date;

            if (! $startDate || ! $endDate) {
                return 'Chưa xác định';
            }

            $days = $startDate->diffInDays($endDate) + 1;

            if ($days == 1) {
                return '1 ngày';
            } elseif ($days < 7) {
                return $days.' ngày';
            } elseif ($days < 30) {
                $weeks = ceil($days / 7);

                return $weeks.' tuần';
            } else {
                $months = ceil($days / 30);

                return $months.' tháng';
            }
        } catch (\Exception $e) {
            return 'Chưa xác định';
        }
    }

    /**
     * Get total weeks count from weekly schedule (số tuần trong JSON lịch tuần)
     */
    public function getWeeksCountAttribute()
    {
        return $this->weekly_schedule ? count($this->weekly_schedule) : 0;
    }

    /**
     * Số tuần quy đổi từ start_date → end_date (làm tròn lên theo 7 ngày).
     */
    public function getDurationWeeksAttribute(): ?int
    {
        try {
            if (! $this->start_date || ! $this->end_date) {
                return null;
            }

            $startDate = is_string($this->start_date) ? \Carbon\Carbon::parse($this->start_date) : $this->start_date;
            $endDate = is_string($this->end_date) ? \Carbon\Carbon::parse($this->end_date) : $this->end_date;

            if (! $startDate || ! $endDate) {
                return null;
            }

            $days = max(1, $startDate->diffInDays($endDate) + 1);

            return (int) max(1, (int) ceil($days / 7));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Hiển thị số tuần (vd: "12 tuần")
     */
    public function getDurationWeeksTextAttribute(): string
    {
        $weeks = $this->duration_weeks;

        if ($weeks === null) {
            return '—';
        }

        return $weeks.' tuần';
    }

    /**
     * Check if schedule is currently active
     */
    public function getIsCurrentlyActiveAttribute()
    {
        try {
            if (! $this->is_active) {
                return false;
            }

            if (! $this->start_date || ! $this->end_date) {
                return false;
            }

            // Ensure dates are Carbon instances
            $startDate = is_string($this->start_date) ? \Carbon\Carbon::parse($this->start_date) : $this->start_date;
            $endDate = is_string($this->end_date) ? \Carbon\Carbon::parse($this->end_date) : $this->end_date;

            if (! $startDate || ! $endDate) {
                return false;
            }

            $now = now();

            return $startDate <= $now && $endDate >= $now;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get unique instructors participating in this training schedule (through schedule details).
     */
    public function instructors()
    {
        return $this->belongsToMany(
            Instructor::class,
            'schedule_details',  // Pivot table
            'training_schedule_id',  // Foreign key trên TrainingSchedule
            'instructor_id'  // Foreign key trên Instructor
        )->withTimestamps()  // Optional: Nếu cần timestamps trên pivot
            ->distinct();  // Unique instructors (tránh duplicate)
    }
}
