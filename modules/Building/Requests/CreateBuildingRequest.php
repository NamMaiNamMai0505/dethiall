<?php

namespace Modules\Building\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBuildingRequest extends FormRequest
{
    public function authorize()
    {
        return true; // đã check quyền bằng middleware
    }

    public function rules()
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:buildings,code'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:1,0'],
        ];
    }

    public function messages()
    {
        return [
            'code.required' => 'Mã giảng đường là bắt buộc.',
            'code.unique'   => 'Mã giảng đường đã tồn tại.',
            'name.required' => 'Tên giảng đường là bắt buộc.',
        ];
    }
}
