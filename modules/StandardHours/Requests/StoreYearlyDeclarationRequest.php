<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\StandardHours\Services\PeriodService;

class StoreYearlyDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'declaration_from_date' => ['required', 'date'],
            'declaration_to_date' => ['required', 'date', 'after_or_equal:declaration_from_date'],
            'object_type_id' => ['required', 'exists:standard_object_types,id'],
            'position_id' => ['required', 'exists:standard_positions,id'],
            'other_teaching_hours' => ['required', 'numeric', 'min:0', 'max:100000'],
            'other_teaching_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'instructor_id' => 'Giảng viên',
            'declaration_from_date' => 'Từ ngày',
            'declaration_to_date' => 'Đến ngày',
            'object_type_id' => 'Đối tượng',
            'position_id' => 'Chức danh',
            'other_teaching_hours' => 'Giờ giảng dạy khác',
            'other_teaching_notes' => 'Nội dung giờ giảng dạy khác',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'other_teaching_hours' => $this->input('other_teaching_hours', 0),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['declaration_from_date', 'declaration_to_date'])
                || ! $this->filled('declaration_from_date')
                || ! $this->filled('declaration_to_date')) {
                return;
            }

            $periodService = app(PeriodService::class);
            if (! $periodService->rangeBelongsToPeriod(
                $this->input('declaration_from_date'),
                $this->input('declaration_to_date')
            )) {
                $validator->errors()->add(
                    'declaration_to_date',
                    'Một hồ sơ giờ chuẩn chỉ được kê khai trong cùng một '
                        .mb_strtolower($periodService->modeLabel()).'.'
                );
            }
        });
    }
}
