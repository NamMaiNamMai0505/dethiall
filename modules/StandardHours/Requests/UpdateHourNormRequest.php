<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\StandardHours\Models\HourNorm;

class UpdateHourNormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hourNorm = $this->route('hourNorm');
        $hourNormId = $hourNorm instanceof HourNorm ? $hourNorm->id : $hourNorm;

        return [
            'object_type_id' => ['required', 'exists:standard_object_types,id'],
            'position_id' => ['required', 'exists:standard_positions,id'],
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2200',
                Rule::unique('standard_hour_norms')->where(function ($query) {
                    return $query
                        ->where('object_type_id', $this->input('object_type_id'))
                        ->where('position_id', $this->input('position_id'))
                        ->whereNull('deleted_at');
                })->ignore($hourNormId),
            ],
            'standard_hours' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'object_type_id' => 'Đối tượng',
            'position_id' => 'Chức danh',
            'year' => 'Năm',
            'standard_hours' => 'Số giờ chuẩn',
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
