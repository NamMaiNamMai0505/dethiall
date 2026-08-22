<?php

namespace Modules\Classroom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'status',
        'created_by',
        'updated_by',
        'building_id'
    ];

    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get status options
     */
    public static function getStatusOptions()
    {
        return [
            1 => 'Hoạt động',
            0 => 'Ngừng hoạt động'
        ];
    }

    /**
     * Get status label attribute
     */
    public function getStatusLabelAttribute()
    {
        return self::getStatusOptions()[$this->status] ?? 'Không xác định';
    }

    /**
     * Get the user who created the classroom
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the classroom
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active classrooms
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include inactive classrooms
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    public function building()
    {
        return $this->belongsTo(\Modules\Building\Models\Building::class, 'building_id');
    }

    public function scheduleDetails()
    {
        return $this->hasMany(\Modules\ScheduleDetail\Models\ScheduleDetail::class);
    }
    
}
