<?php

namespace Modules\Grades\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Class\Models\ClassModel;

class ConductRecord extends Model
{
    protected $table = 'grade_conduct_records';

    protected $fillable = [
        'user_id', 'class_id', 'academic_year', 'conduct_rank',
        'discipline', 'suspended', 'note', 'updated_by',
    ];

    protected $casts = [
        'suspended' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
