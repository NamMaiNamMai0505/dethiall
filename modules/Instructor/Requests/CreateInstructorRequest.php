<?php

namespace Modules\Instructor\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Instructor\Models\Instructor;

class CreateInstructorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // 'code' => ['required', 'string', 'max:50', 'unique:instructors,code'],
            'code' => ['string','nullable', 'max:50', 'unique:instructors,code'],
            'email' => ['required', 'email', 'unique:instructors,email'],
            'phone' => ['required', 'string', 'max:20'],
            //'specialization_id' => ['required', 'exists:specializations,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'description' => ['nullable', 'string']
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'Họ và tên',
            'code' => 'Mã giảng viên',
            'email' => 'Email',
            'phone' => 'Số điện thoại',
            'unit_id' => 'Đơn vị',
            'status' => 'Trạng thái',
            'description' => 'Mô tả'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên',
            /* 'code.required' => 'Vui lòng nhập mã giảng viên',
            'code.unique' => 'Mã giảng viên đã tồn tại trong hệ thống', */
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email đã tồn tại trong hệ thống',
            'phone.required' => 'Vui lòng nhập số điện thoại',
            //'specialization_id.required' => 'Vui lòng chọn ngành đào tạo',
            //'specialization_id.exists' => 'Lớp được chọn không tồn tại',
            'unit_id.required'=> 'Vui lòng chọn đơn vị',
            'status.required' => 'Vui lòng chọn trạng thái'
        ];
    }
}
