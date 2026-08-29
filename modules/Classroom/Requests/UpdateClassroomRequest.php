<?php

namespace Modules\Classroom\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassroomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:80', Rule::unique('classrooms', 'code')->ignore($this->classroom->id)],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('classrooms', 'name')->ignore($this->classroom->id)
            ],
            'room_type' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'boolean'],
            'building_id' => ['required', 'exists:buildings,id'],   
            'floor' => ['nullable', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'managing_unit_id' => ['nullable', 'exists:units,id'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên lớp học là bắt buộc.',
            'name.string' => 'Tên lớp học phải là chuỗi ký tự.',
            'name.max' => 'Tên lớp học không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên lớp học đã tồn tại.',
            'status.boolean' => 'Trạng thái phải là true hoặc false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên lớp học',
            'status' => 'trạng thái',
            'building_id' => 'giảng đường',
        ];
    }
}
