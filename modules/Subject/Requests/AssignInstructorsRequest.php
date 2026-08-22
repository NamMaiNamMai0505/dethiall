<?php

namespace Modules\Subject\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignInstructorsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Permission checked by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'instructor_ids' => ['required', 'array', 'min:1'],
            'instructor_ids.*' => ['exists:instructors,id'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'instructor_ids.required' => 'Vui lòng chọn ít nhất một giảng viên.',
            'instructor_ids.min' => 'Vui lòng chọn ít nhất một giảng viên.',
            'instructor_ids.*.exists' => 'Giảng viên được chọn không tồn tại.',
        ];
    }
}
