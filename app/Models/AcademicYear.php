<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = [
        'code', 'start_year', 'end_year', 'name', 'starts_at', 'ends_at',
        'is_current', 'is_active', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'start_year' => 'integer',
        'end_year' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_current' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
