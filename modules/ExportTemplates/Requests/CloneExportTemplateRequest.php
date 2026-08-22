<?php

namespace Modules\ExportTemplates\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloneExportTemplateRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
