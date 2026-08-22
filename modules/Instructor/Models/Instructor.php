<?php

namespace Modules\Instructor\Models;

use App\Models\User;
use Database\Factories\InstructorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instructor extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): InstructorFactory
    {
        return InstructorFactory::new();
    }

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'specialization_id',
        'unit_id',
        'department_id',
        'object_type_id',
        'position_id',
        'status',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($instructor) {
            if (empty($instructor->code)) {
                do {
                    $latestId = self::withTrashed()->max('id') ?? 0;
                    $nextNumber = $latestId + 1;
                    $code = 'GV-'.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                } while (self::withTrashed()->where('code', $code)->exists());

                $instructor->code = $code;
            }
        });
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_ACTIVE => 'Hoạt động',
            self::STATUS_INACTIVE => 'Tạm ngừng',
        ];
    }

    public function getStatusLabelAttribute()
    {
        return self::getStatusOptions()[$this->status] ?? 'Không xác định';
    }

    /**
     * Get the user who created the instructor
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the instructor
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    /** Một GV thuộc 1 ngành đào tạo */
    public function specialization()
    {
        return $this->belongsTo(\Modules\Specialization\Models\Specialization::class);
    }

    /* 1 GV thuộc 1 Unit (khoa) */
    public function unit()
    {
        return $this->belongsTo(\Modules\Unit\Models\Unit::class);
    }

    /** Bộ môn (chỉ dùng giờ chuẩn / vượt DM) */
    public function department()
    {
        return $this->belongsTo(\Modules\StandardHours\Models\AcademicDepartment::class, 'department_id');
    }

    public function objectType()
    {
        return $this->belongsTo(\Modules\StandardHours\Models\ObjectType::class, 'object_type_id');
    }

    public function position()
    {
        return $this->belongsTo(\Modules\StandardHours\Models\Position::class, 'position_id');
    }

    /** Một GV có thể được assign nhiều môn */
    public function teachingAssignment()
    {
        return $this->hasMany(\Modules\TeachingAssignment\Models\TeachingAssignment::class);
    }

    /** Truy cập nhanh: GV dạy nhiều môn */
    public function subjects()
    {
        return $this->belongsToMany(
            \Modules\Subject\Models\Subject::class,
            'teaching_assignment',
            'instructor_id',
            'subject_id'
        )->withTimestamps();
    }

    /** Lịch chi tiết GV dạy */
    public function scheduleDetails()
    {
        return $this->hasMany(\Modules\ScheduleDetail\Models\ScheduleDetail::class);
    }

    /**
     * Get the user account associated with this instructor (for login)
     */
    public function user()
    {
        return $this->hasOne(User::class, 'instructor_id');
    }

    /**
     * Check if this instructor has a user account for login
     */
    public function hasUserAccount(): bool
    {
        return $this->user()->exists();
    }

    /**
     * Scope to get instructors with user accounts
     */
    public function scopeWithUserAccount($query)
    {
        return $query->has('user');
    }

    /**
     * Scope to get instructors without user accounts
     */
    public function scopeWithoutUserAccount($query)
    {
        return $query->doesntHave('user');
    }
}
