<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RetrieveTeachingScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
        ];
    }
}
