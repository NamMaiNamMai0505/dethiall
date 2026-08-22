<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\StandardHours\Models\ResearchNorm;

class UpdateResearchNormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $researchNorm = $this->route('researchNorm');
        $researchNormId = $researchNorm instanceof ResearchNorm ? $researchNorm->id : $researchNorm;

        return [
            'object_type_id' => ['required', 'exists:standard_object_types,id'],
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2200',
                Rule::unique('research_hour_norms')->where(function ($query) {
                    return $query
                        ->where('object_type_id', $this->input('object_type_id'))
                        ->whereNull('deleted_at');
                })->ignore($researchNormId),
            ],
            'research_hours' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'object_type_id' => 'Đối tượng',
            'year' => 'Năm',
            'research_hours' => 'Số giờ NCKH',
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
