<?php

namespace Modules\Class\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'code',
        'specialization_id',
        'instructor_id',
        'start_date',
        'end_date',
        'duration_months',
        'management_unit',
        'classroom_id',
        'max_students',
        'current_students',
        'is_active',
        'description',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'duration_months' => 'integer',
        'max_students' => 'integer',
        'current_students' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Relationships

    /**
     * Get the specialization that owns the class
     */
    public function specialization()
    {
        return $this->belongsTo(\Modules\Specialization\Models\Specialization::class);
    }

    /**
     * Get the instructor that teaches the class
     */
    public function instructor()
    {
        return $this->belongsTo(\Modules\Instructor\Models\Instructor::class);
    }

    /**
     * Get the classroom where the class is held
     */
    public function classroom()
    {
        return $this->belongsTo(\Modules\Classroom\Models\Classroom::class);
    }

    /**
     * Get the user who created this class
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this class
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // Scopes

    /**
     * Scope to get active classes
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
        return $query->where(function($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
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
     * Scope to filter by instructor
     */
    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    // Accessors

    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Đang hoạt động' : 'Tạm ngưng';
    }

    public function getStatusColorAttribute()
    {
        return $this->is_active ? 'success' : 'danger';
    }
}
