<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\StandardHours\Models\HourExchangeRecord;

class StoreHourExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('standard-hours.hour-exchanges.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => ['required', 'exists:instructors,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2200'],
            'direction' => ['required', 'in:'.HourExchangeRecord::DIRECTION_NCKH_TO_CM.','.HourExchangeRecord::DIRECTION_CM_TO_NCKH],
            'source_hours' => ['required', 'numeric', 'min:0.01'],
            'target_hours' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['required', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'instructor_id' => 'Giảng viên',
            'year' => 'Năm',
            'direction' => 'Chiều quy đổi',
            'source_hours' => 'Số giờ nguồn',
            'target_hours' => 'Số giờ đích',
            'notes' => 'Ghi chú',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Quyết định bù giờ thuộc thẩm quyền quản lý, không tự gán người kê khai.
    }
}
