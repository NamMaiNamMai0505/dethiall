<?php

namespace Modules\ExportTemplates\Services;

use Illuminate\Support\Str;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Models\ExportTemplateBuilderDocument;
use Modules\ExportTemplates\Models\ExportTemplateVersion;

class BuilderTemplateService
{
    public function create(
        string $name,
        string $scope,
        string $featureKey,
        string $format,
        int $userId,
        ?string $description = null
    ): ExportTemplateVersion {
        $format = in_array($format, ['word', 'excel'], true) ? $format : 'excel';
        $template = ExportTemplate::create([
            'code' => Str::slug($featureKey.'-'.$format.'-'.Str::random(6), '_'),
            'name' => $name,
            'scope' => $scope,
            'module_key' => $scope,
            'feature_key' => $featureKey,
            'output_format' => $format,
            'description' => $description,
            'status' => 'draft',
            'is_active' => false,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $version = $template->versions()->create([
            'version_number' => 1,
            'disk' => 'local',
            'file_path' => null,
            'original_name' => $name.'.'.($format === 'word' ? 'docx' : 'xlsx'),
            'file_extension' => $format === 'word' ? 'docx' : 'xlsx',
            'status' => 'draft',
            'created_by' => $userId,
        ]);

        $version->builderDocument()->create([
            'schema' => BuilderTemplateSchema::empty($format),
            'schema_version' => BuilderTemplateSchema::VERSION,
            'created_by' => $userId,
        ]);

        return $version->load('template', 'builderDocument');
    }

    public function save(ExportTemplateVersion $version, array $schema, int $userId): ExportTemplateBuilderDocument
    {
        $normalized = BuilderTemplateSchema::normalize($schema, $version->template?->output_format?->value ?? 'excel');
        BuilderTemplateSchema::validate($normalized);

        return $version->builderDocument()->updateOrCreate(
            [],
            ['schema' => $normalized, 'schema_version' => BuilderTemplateSchema::VERSION, 'created_by' => $userId]
        );
    }

    public function createVersion(ExportTemplateVersion $source, int $userId): ExportTemplateVersion
    {
        $source->loadMissing(['template', 'builderDocument']);
        if (! $source->template || ! $source->builderDocument) {
            throw new \DomainException('Version nguồn không phải Builder Template hợp lệ.');
        }
        $next = ((int) $source->template->versions()->withTrashed()->max('version_number')) + 1;
        $version = $source->template->versions()->create([
            'version_number' => $next,
            'disk' => 'local',
            'file_path' => null,
            'original_name' => $source->original_name,
            'file_extension' => $source->file_extension,
            'manifest' => $source->manifest,
            'status' => 'draft',
            'created_by' => $userId,
        ]);
        $version->builderDocument()->create([
            'schema' => $source->builderDocument->schema,
            'schema_version' => $source->builderDocument->schema_version,
            'created_by' => $userId,
        ]);
        return $version->load('template', 'builderDocument');
    }
}
