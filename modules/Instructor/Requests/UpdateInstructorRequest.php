<?php

namespace Modules\Instructor\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstructorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('instructors')->ignore($this->route('instructor'))],
            'email' => ['required', 'email', Rule::unique('instructors')->ignore($this->route('instructor'))],
            'phone' => ['required', 'string', 'max:20'],
            'specialization_id' => ['nullable', 'exists:specializations,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'Họ và tên',
            'code' => 'Mã giảng viên',
            'email' => 'Email',
            'phone' => 'Số điện thoại',
            'specialization_id' => 'Chuyên môn',
            'unit_id' => 'Đơn vị',
            'status' => 'Trạng thái',
            'description' => 'Mô tả',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên',
            'code.unique' => 'Mã giảng viên đã tồn tại trong hệ thống',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email đã tồn tại trong hệ thống',
            'phone.required' => 'Vui lòng nhập số điện thoại',
            'specialization_id.exists' => 'Chuyên môn được chọn không tồn tại',
            'unit_id.required' => 'Vui lòng chọn đơn vị',
            'unit_id.exists' => 'Đơn vị được chọn không tồn tại',
            'status.required' => 'Vui lòng chọn trạng thái',
            'status.in' => 'Trạng thái không hợp lệ',
        ];
    }
}
