<?php

namespace Modules\Unit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Instructor\Models\Instructor;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    public const FUNCTIONAL_OTHER = 'other';

    public const FUNCTIONAL_TRAINING_OFFICE = 'training_office';

    public const FUNCTIONAL_FACULTY = 'faculty';

    public const FUNCTIONAL_APPROVAL_AGENCY = 'approval_agency';

    protected $fillable = [
        'code',
        'name',
        'abbreviation',
        'parent_id',
        'level',
        'functional_type',
        'faculty_code',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
        'level' => 'integer',
        'functional_type' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static function getStatusOptions()
    {
        return [
            self::STATUS_ACTIVE => 'Hoạt động',
            self::STATUS_INACTIVE => 'Tạm ngừng',
        ];
    }

    /** @return array<string, string> */
    public static function getFunctionalTypeOptions(): array
    {
        return [
            self::FUNCTIONAL_OTHER => 'Đơn vị thông thường',
            self::FUNCTIONAL_TRAINING_OFFICE => 'Phòng Đào tạo',
            self::FUNCTIONAL_FACULTY => 'Khoa chuyên môn',
            self::FUNCTIONAL_APPROVAL_AGENCY => 'Cơ quan phê duyệt',
        ];
    }

    public function getFunctionalTypeLabelAttribute(): string
    {
        return self::getFunctionalTypeOptions()[$this->functional_type]
            ?? self::getFunctionalTypeOptions()[self::FUNCTIONAL_OTHER];
    }

    public function getStatusLabelAttribute()
    {
        return self::getStatusOptions()[$this->status] ?? 'Không xác định';
    }

    /**
     * Get the parent unit
     */
    public function parent()
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    /**
     * Get the child units
     */
    public function children()
    {
        return $this->hasMany(Unit::class, 'parent_id');
    }

    /**
     * Get all descendants
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * Get all instructors in this unit
     */
    public function instructors()
    {
        return $this->hasMany(Instructor::class, 'unit_id');
    }

    /**
     * Get active instructors in this unit
     */
    public function activeInstructors()
    {
        return $this->instructors()->where('status', Instructor::STATUS_ACTIVE);
    }

    /**
     * Get the user who created the unit
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the unit
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active units
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
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
     * Get formatted name with level indentation
     */
    public function getFormattedNameAttribute()
    {
        return str_repeat('—', $this->level - 1).' '.$this->name;
    }

    /**
     * Get total instructors count (including descendants if needed)
     */
    public function getTotalInstructorsCountAttribute()
    {
        return $this->instructors()->count();
    }

    /**
     * Get active instructors count
     */
    public function getActiveInstructorsCountAttribute()
    {
        return $this->activeInstructors()->count();
    }

    /**
     * Check if unit is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Scope to get root units (level 1 or parent_id is null)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id')->orWhere('level', 1);
    }

    /**
     * Scope to get units by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Get full hierarchy path (for breadcrumbs or display)
     */
    public function getHierarchyPathAttribute()
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);

    }

    public function leafFirstHierarchyPath(string $separator = ', '): string
    {
        $path = [];

        for ($unit = $this; $unit; $unit = $unit->parent) {
            $name = trim((string) $unit->name);
            if ($name !== '') {
                $path[] = $name;
            }
        }

        return implode($separator, $path);
    }
}
