<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:standard_positions,name'],
            'description' => ['nullable', 'string'],
            'ratio_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_classroom_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên chức danh',
            'description' => 'Mô tả',
            'ratio_percent' => 'Tỷ lệ chức danh',
            'min_classroom_percent' => 'Tỷ lệ tối thiểu đứng lớp',
            'is_active' => 'Trạng thái',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'min_classroom_percent' => $this->input('min_classroom_percent', 50),
        ]);
    }
}
