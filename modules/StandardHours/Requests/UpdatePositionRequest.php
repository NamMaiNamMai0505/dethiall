<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\StandardHours\Models\Position;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $position = $this->route('position');
        $positionId = $position instanceof Position ? $position->id : $position;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('standard_positions', 'name')->ignore($positionId),
            ],
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
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);
        }
    }
}
