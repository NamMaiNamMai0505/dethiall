<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\StandardHours\Models\ResearchCategory;

class UpdateResearchCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('researchCategory');
        $categoryId = $category instanceof ResearchCategory ? $category->id : $category;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('research_categories', 'code')->ignore($categoryId)],
            'name' => ['required', 'string', 'max:255', Rule::unique('research_categories', 'name')->ignore($categoryId)],
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
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);
        }
    }
}
