<?php

namespace Modules\ExportTemplates\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateExportTemplateVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user && (
            $user->can('export-templates.edit')
            || $user->isSuperAdmin()
            || $user->isManager()
        ));
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:xlsx,xls,xlsm,xlsb,docx', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.extensions' => 'Phiên bản mới phải là DOCX hoặc Excel XLS/XLSX/XLSM/XLSB và cùng loại với template.',
        ];
    }
}
