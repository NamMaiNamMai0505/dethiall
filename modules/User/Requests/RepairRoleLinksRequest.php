<?php

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RepairRoleLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $ids = collect((array) $this->input('user_ids'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->merge(['user_ids' => $ids]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1', 'max:100'],
            'user_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'user_ids.required' => 'Vui lòng chọn ít nhất một tài khoản có thể đồng bộ an toàn.',
            'user_ids.min' => 'Vui lòng chọn ít nhất một tài khoản có thể đồng bộ an toàn.',
            'user_ids.max' => 'Mỗi lần chỉ được đồng bộ tối đa 100 tài khoản.',
            'user_ids.*.exists' => 'Tài khoản được chọn không còn tồn tại.',
        ];
    }
}
