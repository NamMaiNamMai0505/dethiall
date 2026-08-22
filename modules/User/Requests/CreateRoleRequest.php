<?php

namespace Modules\User\Requests;

use App\Support\ApplicationRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Support\RoleMatrixInput;

class CreateRoleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(RoleMatrixInput::normalize($this));
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['array'],
            'abilities.*.*' => ['string', Rule::in(ApplicationRegistry::actionOrder())],
            'extra_permissions' => ['nullable', 'array'],
            'extra_permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'Tên vai trò',
            'abilities' => 'Ma trận phân quyền',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập tên vai trò.',
            'name.unique' => 'Tên vai trò đã tồn tại.',
            'abilities.required' => 'Vui lòng tích ít nhất một ứng dụng cho vai trò này.',
            'abilities.min' => 'Vui lòng tích ít nhất một ứng dụng cho vai trò này.',
            'abilities.*.*.in' => 'Thao tác không hợp lệ.',
            'extra_permissions.*.exists' => 'Quyền không hợp lệ.',
        ];
    }
}
