<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\StandardHours\Models\ObjectType;

class UpdateObjectTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $objectType = $this->route('objectType');
        $objectTypeId = $objectType instanceof ObjectType
            ? $objectType->id
            : $objectType;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('standard_object_types', 'code')->ignore($objectTypeId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('standard_object_types', 'name')->ignore($objectTypeId),
            ],
            'description' => ['nullable', 'string'],
            'standard_hours' => ['required', 'numeric', 'min:0', 'max:99999'],
            'research_hours' => ['required', 'numeric', 'min:0', 'max:99999'],
            'administrative_hours' => ['required', 'numeric', 'min:0', 'max:99999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'code' => strtoupper(trim((string) $this->input('code', ''))),
        ]);
    }

    public function attributes(): array
    {
        return [
            'code' => 'Mã đối tượng',
            'name' => 'Tên đối tượng',
            'description' => 'Mô tả',
            'standard_hours' => 'Định mức giờ chuẩn',
            'research_hours' => 'Định mức NCKH',
            'administrative_hours' => 'Giờ hành chính',
            'is_active' => 'Trạng thái',
        ];
    }
}
