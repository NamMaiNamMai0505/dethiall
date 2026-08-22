<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\StandardHours\Services\PeriodService;

class UpdatePeriodModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('standard-hours.override-approved') ?? false;
    }

    public function rules(): array
    {
        return [
            'period_mode' => ['required', Rule::in(PeriodService::MODES)],
        ];
    }

    public function attributes(): array
    {
        return [
            'period_mode' => 'Chế độ kỳ tính',
        ];
    }
}
