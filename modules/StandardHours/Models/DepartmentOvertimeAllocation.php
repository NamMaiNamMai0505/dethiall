<?php

namespace Modules\StandardHours\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Instructor\Models\Instructor;

class DepartmentOvertimeAllocation extends Model
{
    protected $table = 'department_overtime_allocations';

    protected $fillable = [
        'pool_id', 'instructor_id', 'allocated_hours', 'note',
    ];

    protected $casts = [
        'allocated_hours' => 'float',
    ];

    public function pool(): BelongsTo
    {
        return $this->belongsTo(DepartmentOvertimePool::class, 'pool_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
