<?php

namespace Modules\Student\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studentId = $this->route('student')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($studentId)->whereNull('deleted_at'),
            ],
            'password' => ['nullable', 'string', 'confirmed', 'min:6'],
            'class_id' => ['required', 'exists:classes,id'],
            'status' => ['nullable', 'in:1,0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Họ và tên là bắt buộc.',
            'name.max' => 'Họ và tên không được vượt quá 255 ký tự.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'class_id.required' => 'Lớp học là bắt buộc.',
            'class_id.exists' => 'Lớp học không tồn tại.',
        ];
    }
}
