<?php

namespace Modules\Grades\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeColumn extends Model
{
    protected $table = 'grade_columns';

    protected $fillable = [
        'grade_book_id', 'code', 'name', 'source', 'max_score',
        'weight', 'sort_order', 'is_locked', 'pdot_only',
    ];

    protected $casts = [
        'max_score' => 'float',
        'weight' => 'float',
        'is_locked' => 'boolean',
        'pdot_only' => 'boolean',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(GradeBook::class, 'grade_book_id');
    }

    public function cells(): HasMany
    {
        return $this->hasMany(GradeCell::class, 'grade_column_id');
    }
}
