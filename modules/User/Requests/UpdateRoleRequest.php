<?php

namespace Modules\User\Requests;

use App\Support\ApplicationRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Support\RoleMatrixInput;

class UpdateRoleRequest extends FormRequest
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
        $roleId = $this->route('role')->id;

        return [
            'name' => ['required', 'string', 'max:255', "unique:roles,name,{$roleId}"],
            // Bỏ trống = thu hồi toàn bộ quyền của vai trò, đây là thao tác hợp lệ.
            'abilities' => ['nullable', 'array'],
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
            'abilities.*.*.in' => 'Thao tác không hợp lệ.',
            'extra_permissions.*.exists' => 'Quyền không hợp lệ.',
        ];
    }
}
