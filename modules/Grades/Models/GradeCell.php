<?php

namespace Modules\Grades\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeCell extends Model
{
    protected $table = 'grade_cells';

    protected $fillable = [
        'grade_book_id', 'grade_column_id', 'user_id',
        'score', 'note', 'updated_by',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(GradeBook::class, 'grade_book_id');
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(GradeColumn::class, 'grade_column_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
