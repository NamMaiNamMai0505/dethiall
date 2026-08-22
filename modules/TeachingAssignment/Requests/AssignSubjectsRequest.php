<?php

namespace Modules\TeachingAssignment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => ['required', 'exists:instructors,id'],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['exists:subjects,id']
        ];
    }

    public function messages(): array
    {
        return [
            'instructor_id.required' => 'Vui lòng chọn giảng viên.',
            'subject_ids.required' => 'Vui lòng chọn ít nhất một môn học.'
        ];
    }
}
