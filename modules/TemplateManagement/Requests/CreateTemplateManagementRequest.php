<?php

namespace Modules\TemplateManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class CreateTemplateManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isSuperAdmin() || auth()->user()->can('template-management.create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'scope' => 'required|string|in:dashboard,lms,grades,shared',
            'file' => 'required|file|mimes:xlsx,xls,xlsm,docx,pdf,xlsb|max:10240',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'File phải là Excel hoặc Word.',
        ];
    }
}