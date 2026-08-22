<?php

namespace Modules\ExportTemplates\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Models\ExportTemplateActivation;
use Modules\ExportTemplates\Models\ExportTemplateAuditLog;
use Modules\ExportTemplates\Models\ExportTemplateVersion;
use Modules\ExportTemplates\Services\Parsers\TemplateStructureAnalyzer;

class TemplateLifecycleService
{
    public function __construct(
        private readonly TemplateScanner $scanner,
        private readonly TemplateActivationService $activationService,
        private readonly TemplateStructureAnalyzer $structureAnalyzer
    ) {}

    /**
     * @param  array{name:string, feature_key:string, notes?:string|null}  $data
     */
    public function createTemplate(
        array $data,
        UploadedFile $file,
        string $moduleKey,
        ?int $actorId = null
    ): ExportTemplate {
        $format = $this->formatFromFile($file);
        $code = $this->newTemplateCode($data['feature_key'], $format);
        $storedPath = null;

        try {
            $storedPath = $this->storeUploadedFile(
                $file,
                $moduleKey,
                $code,
                1
            );
            $manifest = $this->scanManifest('local', $storedPath);

            return DB::transaction(function () use (
                $data,
                $file,
                $moduleKey,
                $format,
                $code,
                $storedPath,
                $manifest,
                $actorId
            ): ExportTemplate {
                $template = ExportTemplate::query()->create([
                    'code' => $code,
                    'name' => $data['name'],
                    'scope' => $moduleKey,
                    'module_key' => $moduleKey,
                    'feature_key' => $data['feature_key'],
                    'output_format' => $format,
                    'file_path' => $storedPath,
                    'disk' => 'local',
                    'mime' => $file->getMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                    'placeholders' => $manifest['placeholders'],
                    'cell_map' => [
                        'map' => $manifest['cell_map'],
                        'hints' => $manifest['hints'],
                    ],
                    'notes' => $data['notes'] ?? null,
                    'description' => $data['notes'] ?? null,
                    'status' => TemplateStatus::DRAFT,
                    'is_active' => false,
                    'created_by' => $actorId,
                ]);

                $version = $template->versions()->create(
                    $this->versionAttributes(
                        $file,
                        $storedPath,
                        1,
                        $manifest,
                        $actorId
                    )
                );

                $this->audit(
                    $template,
                    $version,
                    ExportTemplateAuditLog::ACTION_TEMPLATE_CREATED,
                    $actorId,
                    [
                        'module_key' => $moduleKey,
                        'feature_key' => $template->feature_key,
                        'output_format' => $format->value,
                    ]
                );

                return $template->load('latestVersion');
            }, 3);
        } catch (\Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function createVersion(
        ExportTemplate $template,
        UploadedFile $file,
        ?int $actorId = null
    ): ExportTemplateVersion {
        $format = $this->formatFromFile($file);
        $templateFormat = $template->output_format;

        if (! $templateFormat instanceof OutputFormat || $templateFormat !== $format) {
            throw new \DomainException('Định dạng file mới phải trùng với loại template.');
        }

        if (in_array($template->status, [TemplateStatus::ARCHIVED, TemplateStatus::INVALID], true)) {
            throw new \DomainException('Không thể tạo phiên bản cho template đã lưu trữ/không hợp lệ.');
        }

        $storedPath = null;

        try {
            return DB::transaction(function () use (
                $template,
                $file,
                $actorId,
                &$storedPath
            ): ExportTemplateVersion {
                $lockedTemplate = ExportTemplate::query()
                    ->whereKey($template->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $nextVersion = ((int) $lockedTemplate->versions()
                    ->withTrashed()
                    ->max('version_number')) + 1;

                $storedPath = $this->storeUploadedFile(
                    $file,
                    $lockedTemplate->module_key ?: $lockedTemplate->scope,
                    $lockedTemplate->code,
                    $nextVersion
                );
                $manifest = $this->scanManifest('local', $storedPath);

                $version = $lockedTemplate->versions()->create(
                    $this->versionAttributes(
                        $file,
                        $storedPath,
                        $nextVersion,
                        $manifest,
                        $actorId
                    )
                );

                $this->audit(
                    $lockedTemplate,
                    $version,
                    ExportTemplateAuditLog::ACTION_VERSION_CREATED,
                    $actorId,
                    ['version_number' => $nextVersion]
                );

                return $version;
            }, 3);
        } catch (\Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function cloneTemplate(
        ExportTemplate $source,
        ?string $name = null,
        ?int $actorId = null
    ): ExportTemplate {
        $sourceVersion = $source->versions()->with('bindings')->first();
        if (! $sourceVersion) {
            throw new \DomainException('Template nguồn chưa có phiên bản để clone.');
        }

        $format = $source->output_format;
        if (! $format instanceof OutputFormat) {
            throw new \DomainException('Template nguồn chưa có định dạng hợp lệ.');
        }

        $sourceVersion->loadMissing('builderDocument');
        if ($sourceVersion->builderDocument?->schema) {
            return DB::transaction(function () use ($source, $sourceVersion, $name, $actorId, $format): ExportTemplate {
                $code = $this->newTemplateCode($source->feature_key, $format);
                $clone = ExportTemplate::query()->create([
                    'code' => $code,
                    'name' => trim((string) $name) ?: $source->name.' (Bản sao)',
                    'scope' => $source->scope,
                    'module_key' => $source->module_key ?: $source->scope,
                    'feature_key' => $source->feature_key,
                    'output_format' => $format,
                    'description' => $source->description,
                    'notes' => $source->notes,
                    'status' => TemplateStatus::DRAFT,
                    'is_active' => false,
                    'created_by' => $actorId,
                ]);
                $version = $clone->versions()->create([
                    'version_number' => 1,
                    'disk' => 'local',
                    'file_path' => null,
                    'original_name' => $sourceVersion->original_name,
                    'file_extension' => $sourceVersion->file_extension,
                    'manifest' => $sourceVersion->manifest,
                    'status' => TemplateStatus::DRAFT,
                    'created_by' => $actorId,
                ]);
                $version->builderDocument()->create([
                    'schema' => $sourceVersion->builderDocument->schema,
                    'schema_version' => $sourceVersion->builderDocument->schema_version,
                    'created_by' => $actorId,
                ]);
                return $clone->load('latestVersion');
            }, 3);
        }

        $code = $this->newTemplateCode($source->feature_key, $format);
        $extension = $sourceVersion->file_extension
            ?: pathinfo((string) $sourceVersion->original_name, PATHINFO_EXTENSION);
        $targetPath = $this->storagePath(
            $source->module_key ?: $source->scope,
            $code,
            1,
            $extension
        );
        $disk = Storage::disk($sourceVersion->disk ?: 'local');

        if (! $disk->exists($sourceVersion->file_path)) {
            throw new \RuntimeException('Không tìm thấy file của phiên bản nguồn.');
        }
        if (! $disk->copy($sourceVersion->file_path, $targetPath)) {
            throw new \RuntimeException('Không thể sao chép file template.');
        }

        try {
            return DB::transaction(function () use (
                $source,
                $sourceVersion,
                $name,
                $actorId,
                $code,
                $targetPath,
                $format
            ): ExportTemplate {
                $clone = ExportTemplate::query()->create([
                    'code' => $code,
                    'name' => trim((string) $name) ?: $source->name.' (Bản sao)',
                    'scope' => $source->scope,
                    'module_key' => $source->module_key ?: $source->scope,
                    'feature_key' => $source->feature_key,
                    'output_format' => $format,
                    'file_path' => $targetPath,
                    'disk' => $sourceVersion->disk,
                    'mime' => $sourceVersion->mime,
                    'original_name' => $sourceVersion->original_name,
                    'placeholders' => $sourceVersion->manifest['placeholders'] ?? [],
                    'cell_map' => [
                        'map' => $sourceVersion->manifest['cell_map'] ?? [],
                        'hints' => $sourceVersion->manifest['hints'] ?? [],
                    ],
                    'notes' => $source->notes,
                    'description' => $source->description,
                    'status' => TemplateStatus::DRAFT,
                    'is_active' => false,
                    'created_by' => $actorId,
                ]);

                $version = $clone->versions()->create([
                    'version_number' => 1,
                    'disk' => $sourceVersion->disk,
                    'file_path' => $targetPath,
                    'original_name' => $sourceVersion->original_name,
                    'mime' => $sourceVersion->mime,
                    'file_extension' => $sourceVersion->file_extension,
                    'file_size' => $sourceVersion->file_size,
                    'checksum_sha256' => $sourceVersion->checksum_sha256,
                    'manifest' => $sourceVersion->manifest,
                    'status' => TemplateStatus::DRAFT,
                    'created_by' => $actorId,
                ]);

                foreach ($sourceVersion->bindings as $binding) {
                    $version->bindings()->create([
                        'target_ref' => $binding->target_ref,
                        'target_type' => $binding->target_type,
                        'data_key' => $binding->data_key,
                        'binding_type' => $binding->binding_type,
                        'formatter' => $binding->formatter,
                        'options' => $binding->options,
                        'style_overrides' => $binding->style_overrides,
                        'sort_order' => $binding->sort_order,
                    ]);
                }

                $this->audit(
                    $clone,
                    $version,
                    ExportTemplateAuditLog::ACTION_TEMPLATE_CLONED,
                    $actorId,
                    [
                        'source_template_id' => $source->id,
                        'source_version_id' => $sourceVersion->id,
                    ]
                );

                return $clone->load('latestVersion');
            }, 3);
        } catch (\Throwable $exception) {
            $disk->delete($targetPath);

            throw $exception;
        }
    }

    public function activateVersion(
        ExportTemplate $template,
        ExportTemplateVersion $version,
        ?int $actorId = null
    ): void {
        $this->assertVersionBelongsTo($template, $version);
        $this->activationService->activate($version, $actorId);
    }

    public function recordDownload(
        ExportTemplate $template,
        ExportTemplateVersion $version,
        ?int $actorId = null
    ): void {
        $this->assertVersionBelongsTo($template, $version);

        $this->audit(
            $template,
            $version,
            ExportTemplateAuditLog::ACTION_DOWNLOADED,
            $actorId,
            ['version_number' => $version->version_number]
        );
    }

    public function analyzeVersion(
        ExportTemplate $template,
        ExportTemplateVersion $version,
        ?int $actorId = null
    ): ExportTemplateVersion {
        $this->assertVersionBelongsTo($template, $version);
        $disk = Storage::disk($version->disk ?: 'local');
        if (! $disk->exists($version->file_path)) {
            throw new \RuntimeException('Không tìm thấy file của phiên bản để phân tích.');
        }

        $manifest = $this->scanManifest($version->disk ?: 'local', $version->file_path);

        return DB::transaction(function () use (
            $template,
            $version,
            $manifest,
            $actorId,
            $disk
        ): ExportTemplateVersion {
            $absolutePath = $disk->path($version->file_path);
            $version->forceFill([
                'manifest' => $manifest,
                'file_size' => filesize($absolutePath) ?: $version->file_size,
                'checksum_sha256' => hash_file('sha256', $absolutePath)
                    ?: $version->checksum_sha256,
            ])->save();

            if ($version->activations()->exists()) {
                $template->forceFill([
                    'placeholders' => $manifest['placeholders'] ?? [],
                    'cell_map' => [
                        'map' => $manifest['cell_map'] ?? [],
                        'hints' => $manifest['hints'] ?? [],
                    ],
                    'updated_by' => $actorId,
                ])->save();
            }

            $this->audit(
                $template,
                $version,
                ExportTemplateAuditLog::ACTION_ANALYZED,
                $actorId,
                [
                    'parser' => $manifest['parser'] ?? null,
                    'schema_version' => $manifest['schema_version'] ?? null,
                    'target_count' => $manifest['summary']['target_count'] ?? 0,
                ]
            );

            return $version->fresh();
        }, 3);
    }

    public function archiveTemplate(
        ExportTemplate $template,
        ?int $actorId = null
    ): void {
        DB::transaction(function () use ($template, $actorId): void {
            $lockedTemplate = ExportTemplate::query()
                ->whereKey($template->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $hasActiveVersion = ExportTemplateActivation::query()
                ->whereHas('version', fn ($query) => $query
                    ->where('template_id', $lockedTemplate->id))
                ->exists();

            if ($hasActiveVersion) {
                throw new \DomainException(
                    'Không thể xóa template đang Active. Hãy kích hoạt template khác trước.'
                );
            }

            $latestVersion = $lockedTemplate->versions()->first();
            $this->audit(
                $lockedTemplate,
                $latestVersion,
                ExportTemplateAuditLog::ACTION_ARCHIVED,
                $actorId,
                ['files_retained' => true]
            );

            $lockedTemplate->forceFill([
                'status' => TemplateStatus::ARCHIVED,
                'is_active' => false,
                'updated_by' => $actorId,
            ])->save();
            $lockedTemplate->versions()->delete();
            $lockedTemplate->delete();
        }, 3);
    }

    private function assertVersionBelongsTo(
        ExportTemplate $template,
        ExportTemplateVersion $version
    ): void {
        if ((int) $version->template_id !== (int) $template->id) {
            throw new \DomainException('Phiên bản không thuộc template đã chọn.');
        }
    }

    private function formatFromFile(UploadedFile $file): OutputFormat
    {
        return OutputFormat::fromExtension($file->getClientOriginalExtension())
            ?? throw new \InvalidArgumentException('Chỉ hỗ trợ template Word hoặc Excel.');
    }

    private function storeUploadedFile(
        UploadedFile $file,
        string $moduleKey,
        string $code,
        int $versionNumber
    ): string {
        $extension = strtolower($file->getClientOriginalExtension());
        $directory = 'export-templates/'
            .$this->safeSegment($moduleKey).'/'
            .$this->safeSegment($code).'/v'.$versionNumber;
        $path = $file->storeAs(
            $directory,
            'template.'.$extension,
            'local'
        );

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Không thể lưu file template.');
        }

        return $path;
    }

    private function storagePath(
        string $moduleKey,
        string $code,
        int $versionNumber,
        string $extension
    ): string {
        return 'export-templates/'
            .$this->safeSegment($moduleKey).'/'
            .$this->safeSegment($code).'/v'.$versionNumber
            .'/template.'.strtolower(trim($extension, '.'));
    }

    /**
     * @return array{scanner:string, placeholders:array, cell_map:array, hints:array}
     */
    private function scanManifest(string $disk, string $path): array
    {
        $absolutePath = Storage::disk($disk)->path($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $manifest = $this->structureAnalyzer->analyze($absolutePath, $extension);
        $legacyScan = $this->scanner->scan($absolutePath);
        $cellMap = array_merge(
            $legacyScan['cell_map'],
            $manifest['cell_map'] ?? []
        );
        $hints = array_values(array_merge(
            $manifest['hints'] ?? [],
            $legacyScan['hints']
        ));

        foreach ($hints as $hint) {
            if (! empty($hint['suggest']) && empty($cellMap[$hint['cell']])) {
                $cellMap[$hint['cell']] = 'label:'.$hint['suggest'];
            }
        }

        $manifest['placeholders'] = array_values(array_unique(array_merge(
            $manifest['placeholders'] ?? [],
            $legacyScan['placeholders']
        )));
        $manifest['cell_map'] = $cellMap;
        $manifest['hints'] = $hints;

        return $manifest;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    private function versionAttributes(
        UploadedFile $file,
        string $storedPath,
        int $versionNumber,
        array $manifest,
        ?int $actorId
    ): array {
        $absolutePath = Storage::disk('local')->path($storedPath);

        return [
            'version_number' => $versionNumber,
            'disk' => 'local',
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'file_extension' => strtolower($file->getClientOriginalExtension()),
            'file_size' => $file->getSize(),
            'checksum_sha256' => hash_file('sha256', $absolutePath) ?: null,
            'manifest' => $manifest,
            'status' => TemplateStatus::DRAFT,
            'created_by' => $actorId,
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(
        ExportTemplate $template,
        ?ExportTemplateVersion $version,
        string $action,
        ?int $actorId,
        array $metadata = []
    ): void {
        ExportTemplateAuditLog::query()->create([
            'template_id' => $template->id,
            'template_version_id' => $version?->id,
            'actor_id' => $actorId,
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }

    private function newTemplateCode(string $featureKey, OutputFormat $format): string
    {
        $prefix = Str::of($featureKey)
            ->replace('.', '_')
            ->slug('_')
            ->limit(80, '')
            ->toString();

        return ($prefix !== '' ? $prefix : 'template')
            .'_'.$format->value.'_'.Str::lower((string) Str::ulid());
    }

    private function safeSegment(string $value): string
    {
        $segment = Str::slug($value, '_');

        return $segment !== '' ? $segment : 'shared';
    }
}
