<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\ApprovalAgency;
use App\Support\ManagementRole;
use App\Support\RoleDisplay;
use App\Support\TrainingDept;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Class\Models\ClassModel;
use Modules\Grades\Services\GradeAccess;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Models\Position;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'password',
        'unit_id',
        'role_id',
        'military_rank_id',
        'status',
        'class_id',
        'instructor_id',
        'position_id',
        'object_type_id',
        'user_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'integer',
            'user_type' => 'string',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the unit this user belongs to.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the role assigned via role_id.
     */
    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function militaryRank()
    {
        return $this->belongsTo(MilitaryRank::class);
    }

    /**
     * Get the class this user belongs to.
     */
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the instructor this user is associated with.
     */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function objectType()
    {
        return $this->belongsTo(ObjectType::class, 'object_type_id');
    }

    public function systemNotifications()
    {
        return $this->hasMany(SystemNotification::class);
    }

    public function digitalSignatures()
    {
        return $this->hasMany(DigitalSignature::class);
    }

    public function isManagementActor(): bool
    {
        return $this->isManager()
            || $this->isSystemManager()
            || $this->isTrainingOfficeManager()
            || $this->isFacultyScheduleManager()
            || $this->isStandardHoursManager()
            || $this->isExamManager()
            || $this->isApprovalAgency()
            || $this->isSuperAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isManager(): bool
    {
        return $this->hasRole(ManagementRole::LEGACY_MANAGER);
    }

    public function isSystemManager(): bool
    {
        return $this->hasRole(ManagementRole::SYSTEM_MANAGER);
    }

    public function isTrainingOfficeManager(): bool
    {
        return $this->hasRole(ManagementRole::TRAINING_OFFICE_MANAGER);
    }

    public function isFacultyScheduleManager(): bool
    {
        return $this->hasRole(ManagementRole::FACULTY_SCHEDULE_MANAGER);
    }

    public function isStandardHoursManager(): bool
    {
        return $this->hasRole(ManagementRole::STANDARD_HOURS_MANAGER);
    }

    public function isExamManager(): bool
    {
        return $this->hasRole(RoleDisplay::EXAM_MANAGER);
    }

    public function isApprovalAgency(): bool
    {
        try {
            return $this->hasRole(RoleDisplay::APPROVAL_AGENCY)
                || ApprovalAgency::isApprovalAgencyUnit($this);
        } catch (\Throwable) {
            return ApprovalAgency::isApprovalAgencyUnit($this);
        }
    }

    /**
     * Được vào portal Quản lý điểm (GV / Manager / Super-admin / permission).
     */
    public function canAccessGrades(): bool
    {
        if ($this->isSuperAdmin() || $this->hasRole('super-admin')) {
            return true;
        }
        if (class_exists(GradeAccess::class)) {
            return GradeAccess::canEnter($this);
        }

        return $this->isManager()
            || $this->isInstructor()
            || $this->can('grades.index')
            || $this->can('grades.manage');
    }

    /**
     * Khoa/đơn vị được phân công quản lý (manager).
     */
    public function managedUnitId(): ?int
    {
        if ((! $this->isManager() && ! $this->isFacultyScheduleManager()) || $this->isSuperAdmin()) {
            return null;
        }

        return $this->unit_id ? (int) $this->unit_id : null;
    }

    public function isUnitScopedManager(): bool
    {
        return ($this->isManager() || $this->isFacultyScheduleManager()) && ! $this->isSuperAdmin();
    }

    /**
     * Tài khoản gắn đơn vị Phòng đào tạo (xếp khung lịch).
     */
    public function isTrainingOffice(): bool
    {
        return TrainingDept::canManageScheduleSkeleton($this);
    }

    /**
     * Manager thuộc khoa (phân công bài/GV) — không phải Phòng ĐT.
     */
    public function isFacultyManager(): bool
    {
        return TrainingDept::isFacultyManager($this);
    }

    /**
     * Check if this user is an instructor
     */
    public function isInstructor(): bool
    {
        return $this->user_type === 'instructor' && $this->instructor_id !== null;
    }

    /**
     * Check if this user is a student
     */
    public function isStudent(): bool
    {
        return $this->user_type === 'student';
    }

    /**
     * Check if this user is an internal user
     */
    public function isInternalUser(): bool
    {
        return $this->user_type === 'internal_user';
    }

    /**
     * Scope to get only instructor users
     */
    public function scopeInstructors($query)
    {
        return $query->where('user_type', 'instructor')->whereNotNull('instructor_id');
    }

    /**
     * Scope to get only student users
     */
    public function scopeStudents($query)
    {
        return $query->where('user_type', 'student');
    }

    /**
     * Scope to get only internal users
     */
    public function scopeInternalUsers($query)
    {
        return $query->where('user_type', 'internal_user');
    }
}
