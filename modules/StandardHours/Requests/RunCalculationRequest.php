<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunCalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2200'],
            'unit_id' => ['nullable', 'exists:units,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'year' => 'Năm',
            'unit_id' => 'Đơn vị',
        ];
    }
}
