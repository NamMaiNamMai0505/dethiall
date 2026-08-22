<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResearchCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:research_categories,code'],
            'name' => ['required', 'string', 'max:255', 'unique:research_categories,name'],
            'unit' => ['nullable', 'string', 'max:50'],
            'research_hours' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Mã danh mục',
            'name' => 'Tên danh mục',
            'unit' => 'Đơn vị tính',
            'research_hours' => 'Số giờ quy đổi',
            'description' => 'Mô tả',
            'is_active' => 'Trạng thái',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
