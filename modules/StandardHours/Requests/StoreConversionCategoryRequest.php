<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\StandardHours\Models\ConversionCategory;

class StoreConversionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:conversion_categories,code'],
            'name' => ['required', 'string', 'max:255', 'unique:conversion_categories,name'],
            'unit' => ['required', 'string', 'max:50'],
            'conversion_method' => ['required', Rule::in([
                ConversionCategory::METHOD_COEFFICIENT,
                ConversionCategory::METHOD_FIXED_HOURS,
            ])],
            'coefficient' => [
                'nullable',
                'required_if:conversion_method,'.ConversionCategory::METHOD_COEFFICIENT,
                'prohibited_if:conversion_method,'.ConversionCategory::METHOD_FIXED_HOURS,
                'numeric',
                'min:0',
            ],
            'fixed_hours' => [
                'nullable',
                'required_if:conversion_method,'.ConversionCategory::METHOD_FIXED_HOURS,
                'prohibited_if:conversion_method,'.ConversionCategory::METHOD_COEFFICIENT,
                'numeric',
                'min:0',
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Mã danh mục',
            'name' => 'Tên hoạt động',
            'unit' => 'Đơn vị tính',
            'conversion_method' => 'Phương thức quy đổi',
            'coefficient' => 'Hệ số quy đổi',
            'fixed_hours' => 'Số giờ cố định',
            'description' => 'Mô tả',
            'is_active' => 'Trạng thái',
        ];
    }

    public function messages(): array
    {
        return [
            'coefficient.prohibited_if' => 'Không được nhập hệ số khi chọn số giờ cố định.',
            'fixed_hours.prohibited_if' => 'Không được nhập số giờ khi chọn hệ số quy đổi.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
