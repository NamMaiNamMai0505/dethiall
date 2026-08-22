<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreObjectTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:standard_object_types,code'],
            'name' => ['required', 'string', 'max:255', 'unique:standard_object_types,name'],
            'description' => ['nullable', 'string'],
            'standard_hours' => ['required', 'numeric', 'min:0', 'max:99999'],
            'research_hours' => ['required', 'numeric', 'min:0', 'max:99999'],
            'administrative_hours' => ['required', 'numeric', 'min:0', 'max:99999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'code' => strtoupper(trim((string) $this->input('code', ''))),
        ]);
    }
}
