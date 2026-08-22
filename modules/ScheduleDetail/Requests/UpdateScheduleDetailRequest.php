<?php

namespace Modules\ScheduleDetail\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleDetailRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // TODO: define rules
        ];
    }
}