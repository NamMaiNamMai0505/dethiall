<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Instructor\Models\Instructor;
use Modules\Unit\Models\Unit;

class AcademicDepartment extends Model
{
    use SoftDeletes;

    protected $table = 'academic_departments';

    protected $fillable = [
        'unit_id', 'code', 'name', 'description',
        'is_active', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function instructors(): HasMany
    {
        return $this->hasMany(Instructor::class, 'department_id');
    }

    public function overtimePools(): HasMany
    {
        return $this->hasMany(DepartmentOvertimePool::class, 'department_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByUnit($query, $unitId)
    {
        if (blank($unitId)) {
            return $query;
        }

        return $query->where('unit_id', $unitId);
    }
}
