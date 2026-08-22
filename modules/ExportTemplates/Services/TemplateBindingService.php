<?php

namespace Modules\ExportTemplates\Services;

use Illuminate\Support\Facades\DB;
use Modules\ExportTemplates\Models\ExportTemplateAuditLog;
use Modules\ExportTemplates\Models\ExportTemplateBinding;
use Modules\ExportTemplates\Models\ExportTemplateVersion;

class TemplateBindingService
{
    public function __construct(
        private readonly BindingSchemaValidator $validator,
        private readonly TemplatePresentationSanitizer $presentationSanitizer
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function bind(
        ExportTemplateVersion $version,
        string $targetRef,
        string $dataKey,
        ?string $formatter = null,
        array $options = [],
        ?int $actorId = null,
        array $styleOverrides = []
    ): ExportTemplateBinding {
        $validated = $this->validator->validate($version, $targetRef, $dataKey);
        $presentation = $this->presentationSanitizer->sanitize(
            $styleOverrides,
            $options,
            (string) ($validated['target']['kind'] ?? '')
        );
        $formatter = trim((string) $formatter);

        if (mb_strlen($formatter) > 128) {
            throw new \InvalidArgumentException('Formatter không được vượt quá 128 ký tự.');
        }

        return DB::transaction(function () use (
            $version,
            $targetRef,
            $dataKey,
            $formatter,
            $presentation,
            $actorId,
            $validated
        ): ExportTemplateBinding {
            $binding = ExportTemplateBinding::query()->updateOrCreate(
                [
                    'template_version_id' => $version->id,
                    'target_ref' => $targetRef,
                ],
                [
                    'target_type' => (string) ($validated['target']['kind'] ?? 'target'),
                    'data_key' => $dataKey,
                    'binding_type' => $validated['binding_type'],
                    'formatter' => $formatter !== '' ? $formatter : null,
                    'options' => $presentation['options'] ?: null,
                    'style_overrides' => $presentation['style'] ?: null,
                ]
            );

            ExportTemplateAuditLog::query()->create([
                'template_id' => $version->template_id,
                'template_version_id' => $version->id,
                'actor_id' => $actorId,
                'action' => ExportTemplateAuditLog::ACTION_BINDING_UPDATED,
                'metadata' => [
                    'binding_id' => $binding->id,
                    'target_ref' => $targetRef,
                    'data_key' => $dataKey,
                    'binding_type' => $binding->binding_type->value,
                    'style_overrides' => array_keys($presentation['style']),
                    'layout_options' => $presentation['options'],
                ],
            ]);

            return $binding->refresh();
        }, 3);
    }

    public function remove(
        ExportTemplateVersion $version,
        ExportTemplateBinding $binding,
        ?int $actorId = null
    ): void {
        if ((int) $binding->template_version_id !== (int) $version->id) {
            throw new \DomainException('Binding không thuộc phiên bản đang chỉnh sửa.');
        }

        // Dùng cùng validator policy bất biến, nhưng không cần target/data còn tồn tại.
        if (
            $version->status !== \Modules\ExportTemplates\Enums\TemplateStatus::DRAFT
            || $version->activations()->exists()
        ) {
            throw new \DomainException('Chỉ được xóa binding trên phiên bản nháp chưa Active.');
        }

        DB::transaction(function () use ($version, $binding, $actorId): void {
            $metadata = [
                'binding_id' => $binding->id,
                'target_ref' => $binding->target_ref,
                'data_key' => $binding->data_key,
            ];
            $binding->delete();

            ExportTemplateAuditLog::query()->create([
                'template_id' => $version->template_id,
                'template_version_id' => $version->id,
                'actor_id' => $actorId,
                'action' => ExportTemplateAuditLog::ACTION_BINDING_DELETED,
                'metadata' => $metadata,
            ]);
        }, 3);
    }
}
