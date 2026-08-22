<?php

namespace Modules\TemplateManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateManagementRequest extends FormRequest
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