<?php

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $permissionId = $this->route('permission')->id;
        return [
            'name' => ['required', 'string', 'max:255', "unique:permissions,name,{$permissionId}"],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'Tên quyền',
            'roles' => 'Vai trò',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập tên quyền',
            'name.unique' => 'Quyền đã tồn tại',
        ];
    }
}

