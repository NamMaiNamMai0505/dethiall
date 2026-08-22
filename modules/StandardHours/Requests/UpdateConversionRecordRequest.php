<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\StandardHours\Support\InstructorScope;

class UpdateConversionRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => ['required', 'exists:instructors,id'],
            'conversion_category_id' => ['required', 'exists:conversion_categories,id'],
            'activity_name' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date'],
            'year' => ['required', 'integer', 'min:2000', 'max:2200'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'has_other_remuneration' => ['sometimes', 'boolean'],
            'is_external_invitation' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_other_remuneration' => $this->boolean('has_other_remuneration'),
            'is_external_invitation' => $this->boolean('is_external_invitation'),
        ]);

        if ($instructorId = InstructorScope::instructorId()) {
            $this->merge(['instructor_id' => $instructorId]);
        }
    }

    public function attributes(): array
    {
        return [
            'instructor_id' => 'Giảng viên',
            'conversion_category_id' => 'Tên hoạt động chuyên môn',
            'activity_name' => 'Chi tiết hoạt động',
            'activity_date' => 'Ngày thực hiện',
            'year' => 'Năm',
            'quantity' => 'Số lượng',
            'has_other_remuneration' => 'Hoạt động đã có chế độ thù lao riêng',
            'is_external_invitation' => 'Hoạt động mời giảng ngoài nhà trường',
            'notes' => 'Ghi chú',
            'evidence' => 'Minh chứng',
        ];
    }
}
