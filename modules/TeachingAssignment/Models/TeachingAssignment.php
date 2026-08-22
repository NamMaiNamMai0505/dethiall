<?php

namespace Modules\TeachingAssignment\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingAssignment extends Model
{
    protected $fillable = [
        'instructor_id','subject_id'
    ];

    /** Assignment thuộc 1 GV */
    public function instructor()
    {
        return $this->belongsTo(\Modules\Instructor\Models\Instructor::class);
    }

    /** Assignment thuộc 1 môn */
    public function subject()
    {
        return $this->belongsTo(\Modules\Subject\Models\Subject::class);
    }
}