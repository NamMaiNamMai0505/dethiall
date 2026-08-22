<?php

namespace Modules\ExportTemplates\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExportTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
