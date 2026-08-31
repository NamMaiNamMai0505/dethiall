<?php

namespace Modules\User\Requests;

use App\Rules\UniqueInstructorUser;
use App\Support\MilitaryRankAssignment;
use App\Support\RoleAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->route('user')->id;
        $unitRequiredRoleIds = RoleAssignment::unitRequiredRoleIds();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'confirmed', 'min:6'],
            'unit_id' => [
                Rule::requiredIf(fn () => in_array((int) $this->input('role_id'), $unitRequiredRoleIds, true)),
                'nullable',
                'exists:units,id',
            ],
            'role_id' => ['required', 'exists:roles,id'],
            'military_rank_id' => [
                'nullable',
                'exists:military_ranks,id',
                Rule::prohibitedIf(fn () => ! MilitaryRankAssignment::allows(
                    (int) $this->input('role_id'),
                    $this->input('user_type')
                )),
            ],
            'user_type' => ['required', 'string', 'in:instructor,internal_user,student'],
            'status' => ['required', 'in:1,0'],
            'position_id' => ['nullable', 'exists:standard_positions,id'],
            'object_type_id' => ['nullable', 'exists:standard_object_types,id'],
            'instructor_id' => [
                'nullable',
                'exists:instructors,id',
                new UniqueInstructorUser($userId),
            ],
            'class_id' => [
                Rule::requiredIf(fn () => $this->input('user_type') === 'student'),
                'nullable',
                'exists:classes,id',
            ],
            'leave_personnel_id' => [
                'nullable',
                'integer',
                Rule::exists('leave_personnel', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
            'leave_position' => ['nullable', 'string', 'max:255', Rule::exists('leave_positions', 'name')->where(fn ($query) => $query->where('active', true))],
        ];
    }

    public function messages()
    {
        return [
            'unit_id.required' => 'Vui lòng chọn đơn vị phù hợp với vai trò quản lý.',
            'instructor_id.exists' => 'Giáo viên được chọn không tồn tại.',
            'class_id.required' => 'Vui lòng chọn lớp học khi tạo tài khoản sinh viên.',
            'class_id.exists' => 'Lớp học được chọn không tồn tại.',
            'military_rank_id.prohibited' => 'Tài khoản Học viên không được gán cấp bậc.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $error = RoleAssignment::roleUnitValidationError(
                (int) $this->input('role_id'),
                $this->filled('unit_id') ? (int) $this->input('unit_id') : null,
                $this->input('user_type')
            );

            if ($error) {
                $validator->errors()->add($error['field'], $error['message']);
            }
        });
    }
}
