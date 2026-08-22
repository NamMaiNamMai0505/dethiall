<?php

namespace Modules\ExportTemplates\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateExportTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user && (
            $user->can('export-templates.create')
            || $user->isSuperAdmin()
            || $user->isManager()
        ));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'feature_key' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/',
            ],
            // Không dùng mimes: MIME của XLSX từ browser/Excel đôi khi là octet-stream.
            // TemplateLifecycle/Parser sẽ kiểm tra ZIP/OXML thực tế sau bước này.
            'file' => ['required', 'file', 'extensions:xlsx,xls,xlsm,xlsb,docx', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'feature_key.regex' => 'Feature key chỉ gồm chữ thường, số, dấu chấm, gạch ngang hoặc gạch dưới.',
            'file.extensions' => 'Phần mở rộng file phải là DOCX, XLS, XLSX, XLSM hoặc XLSB.',
        ];
    }
}
