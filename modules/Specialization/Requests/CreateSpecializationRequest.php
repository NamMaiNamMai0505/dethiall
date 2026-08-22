<?php

namespace Modules\Specialization\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSpecializationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add your authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'training_system_id' => [
                'required',
                'integer',
                'exists:training_systems,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[\pL\pN._-]+$/u',
                'unique:specializations,code,NULL,id,deleted_at,NULL',
            ],
            'major_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[\pL\pN._-]+$/u',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'level' => [
                'required',
                'string',
                'in:beginner,intermediate,advanced,expert',
            ],
            'duration_months' => [
                'required',
                'integer',
                'min:1',
                'max:120',
            ],
            'training_form' => [
                'required',
                'string',
                'in:formal,bridging,conversion',
            ],
            'prerequisites' => [
                'nullable',
                'array',
            ],
            'prerequisites.*' => [
                'string',
                'max:255',
            ],
            'certification_type' => [
                'required',
                'string',
                'in:certificate,secondary_diploma,college_diploma,bachelor_degree,master_degree,doctorate_degree',
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'training_system_id.required' => 'Vui lòng chọn Hệ đào tạo.',
            'training_system_id.exists' => 'Hệ đào tạo không hợp lệ.',
            'name.required' => 'Tên ngành đào tạo là bắt buộc.',
            'name.string' => 'Tên ngành đào tạo phải là chuỗi ký tự.',
            'name.max' => 'Tên ngành đào tạo không được vượt quá :max ký tự.',

            'code.string' => 'Mã số phải là chuỗi ký tự.',
            'code.max' => 'Mã số không được vượt quá :max ký tự.',
            'code.regex' => 'Mã số chỉ được chứa chữ cái, số, dấu chấm, gạch ngang và gạch dưới.',
            'code.unique' => 'Mã số này đã tồn tại.',
            'major_code.required' => 'Mã ngành là bắt buộc.',
            'major_code.regex' => 'Mã ngành chỉ được chứa chữ cái, số, dấu chấm, gạch ngang và gạch dưới.',

            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.max' => 'Mô tả không được vượt quá :max ký tự.',

            'level.required' => 'Cấp độ là bắt buộc.',
            'level.in' => 'Cấp độ không hợp lệ.',

            'duration_months.required' => 'Thời gian đào tạo là bắt buộc.',
            'duration_months.integer' => 'Thời gian đào tạo phải là số nguyên.',
            'duration_months.min' => 'Thời gian đào tạo phải ít nhất :min tháng.',
            'duration_months.max' => 'Thời gian đào tạo không được vượt quá :max tháng.',
            'training_form.required' => 'Hình thức đào tạo là bắt buộc.',
            'training_form.in' => 'Hình thức đào tạo không hợp lệ.',

            'prerequisites.array' => 'Điều kiện tiên quyết phải là một mảng.',
            'prerequisites.*.string' => 'Mỗi điều kiện tiên quyết phải là chuỗi ký tự.',
            'prerequisites.*.max' => 'Mỗi điều kiện tiên quyết không được vượt quá :max ký tự.',

            'certification_type.required' => 'Loại chứng chỉ là bắt buộc.',
            'certification_type.in' => 'Loại chứng chỉ không hợp lệ.',

            'is_active.boolean' => 'Trạng thái hoạt động phải là true hoặc false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'training_system_id' => 'hệ đào tạo',
            'name' => 'tên ngành đào tạo',
            'code' => 'mã số',
            'major_code' => 'mã ngành',
            'description' => 'mô tả',
            'level' => 'đối tượng',
            'duration_months' => 'thời gian đào tạo',
            'training_form' => 'hình thức đào tạo',
            'prerequisites' => 'điều kiện tiên quyết',
            'certification_type' => 'loại chứng chỉ',
            'is_active' => 'trạng thái hoạt động',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_active' => $this->has('is_active') ? (bool) $this->is_active : true,
        ]);

        // Clean up prerequisites array
        if ($this->has('prerequisites') && is_array($this->prerequisites)) {
            $prerequisites = array_filter($this->prerequisites, function ($item) {
                return ! empty(trim($item));
            });
            $this->merge(['prerequisites' => array_values($prerequisites)]);
        }

        // Clean up code
        if ($this->has('code') && ! empty($this->code)) {
            $this->merge([
                'code' => strtoupper(trim($this->code)),
            ]);
        }
    }
}
