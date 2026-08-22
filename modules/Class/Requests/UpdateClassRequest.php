<?php

namespace Modules\Class\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('classes')->ignore($this->class)],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'instructor_id' => ['required', 'exists:instructors,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'duration_months' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive,completed,cancelled'],
            'description' => ['nullable', 'string'],
            'max_students' => ['required', 'integer', 'min:1'],
            'current_students' => ['sometimes', 'integer', 'min:0', 'lte:max_students']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên lớp học là bắt buộc.',
            'name.max' => 'Tên lớp học không được vượt quá 255 ký tự.',
            'code.required' => 'Mã lớp học là bắt buộc.',
            'code.unique' => 'Mã lớp học đã tồn tại.',
            'specialization_id.required' => 'Lớp là bắt buộc.',
            'specialization_id.exists' => 'Lớp không tồn tại.',
            'instructor_id.required' => 'Giảng viên là bắt buộc.',
            'instructor_id.exists' => 'Giảng viên không tồn tại.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.date' => 'Ngày kết thúc không hợp lệ.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'duration_months.required' => 'Thời gian đào tạo là bắt buộc.',
            'duration_months.integer' => 'Thời gian đào tạo phải là số nguyên.',
            'duration_months.min' => 'Thời gian đào tạo phải lớn hơn 0.',
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'max_students.required' => 'Số lượng học viên tối đa là bắt buộc.',
            'max_students.integer' => 'Số lượng học viên tối đa phải là số nguyên.',
            'max_students.min' => 'Số lượng học viên tối đa phải lớn hơn 0.',
            'current_students.integer' => 'Số lượng học viên hiện tại phải là số nguyên.',
            'current_students.min' => 'Số lượng học viên hiện tại không được âm.',
            'current_students.lte' => 'Số lượng học viên hiện tại không được vượt quá số lượng tối đa.'
        ];
    }
}
