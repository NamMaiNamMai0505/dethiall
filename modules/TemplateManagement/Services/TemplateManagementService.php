<?php

namespace Modules\TemplateManagement\Services;

use Modules\ExportTemplates\Models\ExportTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class TemplateManagementService
{
    public function getAllTemplates(?string $scope = null)
    {
        $query = ExportTemplate::query()->orderByDesc('id');

        if ($scope) {
            $query->where(function ($q) use ($scope) {
                $q->where('scope', $scope)->orWhere('scope', ExportTemplate::SCOPE_SHARED);
            });
        }

        return $query->paginate(20);
    }

    public function getActiveTemplate(string $scope)
    {
        return ExportTemplate::query()
            ->where('scope', $scope)
            ->orWhere('scope', ExportTemplate::SCOPE_SHARED)
            ->where('is_active', true)
            ->first();
    }

    public function uploadTemplate(array $data)
    {
        // Logic upload, scan with TemplateScanner if possible, create ExportTemplate
        // For now, reuse the logic from ExportTemplateController
        $file = $data['file'];
        $scope = $data['scope'];

        $path = $file->store('export-templates/' . $scope, 'local');
        $abs = Storage::disk('local')->path($path);

        // Here we can call scanner if we have access
        // For now, create the record
        ExportTemplate::query()->create([
            'name' => $data['name'] ?? 'Template ' . now()->format('Y-m-d'),
            'scope' => $scope,
            'feature_key' => $data['feature_key'] ?? 'template',
            'file_path' => $path,
            'disk' => 'local',
            'mime' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'placeholders' => [], // To be filled by scanner
            'cell_map' => [],
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return true;
    }

    public function setActive(string $id, string $scope)
    {
        // Deactivate old active, activate this one
        ExportTemplate::query()
            ->where('scope', $scope)
            ->update(['is_active' => false]);

        ExportTemplate::query()
            ->where('id', $id)
            ->update(['is_active' => true]);

        return true;
    }
}