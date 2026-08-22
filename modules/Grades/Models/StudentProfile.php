<?php

namespace Modules\Grades\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $table = 'grade_student_profiles';

    protected $fillable = [
        'user_id', 'student_code', 'birth_place', 'ethnicity', 'religion',
        'id_number', 'id_issued_at', 'id_issued_place', 'permanent_address',
        'phone', 'father_name', 'mother_name', 'note', 'updated_by',
    ];

    protected $casts = [
        'id_issued_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
