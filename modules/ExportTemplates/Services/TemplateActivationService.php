<?php

namespace Modules\ExportTemplates\Services;

use Illuminate\Support\Facades\DB;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Models\ExportTemplateActivation;
use Modules\ExportTemplates\Models\ExportTemplateAuditLog;
use Modules\ExportTemplates\Models\ExportTemplateVersion;

class TemplateActivationService
{
    public function __construct(private readonly TemplateDataRegistry $dataRegistry) {}

    public function activate(
        ExportTemplateVersion $version,
        ?int $actorId = null
    ): ExportTemplateActivation {
        $version->loadMissing('template');
        $template = $version->template;

        if (! $template) {
            throw new \DomainException('Phiên bản không thuộc template hợp lệ.');
        }

        if (! $this->dataRegistry->has((string) $template->feature_key)) {
            throw new \DomainException(
                "Không thể kích hoạt feature [{$template->feature_key}] vì chưa có Data Provider."
            );
        }

        $format = $template->output_format;
        if (! $format instanceof OutputFormat) {
            throw new \DomainException('Template chưa có định dạng Word/Excel hợp lệ.');
        }

        if (! $version->status->canBeActivated() || ! $template->status->canBeActivated()) {
            throw new \DomainException('Template hoặc phiên bản đã lưu trữ/không hợp lệ.');
        }

        return DB::transaction(function () use ($version, $template, $format, $actorId) {
            $now = now();

            DB::table('export_template_activations')->upsert(
                [[
                    'feature_key' => $template->feature_key,
                    'output_format' => $format->value,
                    'template_version_id' => $version->id,
                    'activated_by' => $actorId,
                    'activated_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['feature_key', 'output_format'],
                [
                    'template_version_id',
                    'activated_by',
                    'activated_at',
                    'updated_at',
                ]
            );

            // Duy trì cờ legacy cho đến khi toàn bộ exporter chuyển sang resolver mới.
            ExportTemplate::query()
                ->where('feature_key', $template->feature_key)
                ->where('output_format', $format->value)
                ->update(['is_active' => false]);

            // Bulk update phía trên không cập nhật original state của model đang giữ trong bộ nhớ.
            // Ép is_active thành dirty để save() luôn ghi lại true, kể cả kích hoạt version khác
            // của cùng một template.
            $template->syncOriginalAttribute('is_active', false);
            $template->forceFill([
                'is_active' => true,
                'status' => TemplateStatus::PUBLISHED,
                'updated_by' => $actorId,
                // Các cột legacy luôn trỏ tới version active để exporter cũ không đọc nhầm bản nháp.
                'file_path' => $version->file_path,
                'disk' => $version->disk,
                'mime' => $version->mime,
                'original_name' => $version->original_name,
                'placeholders' => $version->manifest['placeholders'] ?? [],
                'cell_map' => [
                    'map' => $version->manifest['cell_map'] ?? [],
                    'hints' => $version->manifest['hints'] ?? [],
                ],
            ])->save();
            DB::table('export_templates')
                ->where('id', $template->id)
                ->update(['is_active' => true]);
            $template->syncOriginalAttribute('is_active', true);

            if ($version->status !== TemplateStatus::PUBLISHED) {
                $version->forceFill(['status' => TemplateStatus::PUBLISHED])->save();
            }

            ExportTemplateAuditLog::query()->create([
                'template_id' => $template->id,
                'template_version_id' => $version->id,
                'actor_id' => $actorId,
                'action' => ExportTemplateAuditLog::ACTION_ACTIVATED,
                'metadata' => [
                    'feature_key' => $template->feature_key,
                    'output_format' => $format->value,
                    'version_number' => $version->version_number,
                ],
            ]);

            return ExportTemplateActivation::query()
                ->where('feature_key', $template->feature_key)
                ->where('output_format', $format->value)
                ->firstOrFail();
        }, 3);
    }

    public function deactivate(
        string $featureKey,
        OutputFormat|string $format,
        ?int $actorId = null
    ): bool {
        $format = $format instanceof OutputFormat
            ? $format
            : OutputFormat::tryFrom(strtolower(trim($format)));

        if (! $format) {
            throw new \InvalidArgumentException('Định dạng template không hợp lệ.');
        }

        return DB::transaction(function () use ($featureKey, $format, $actorId): bool {
            $activation = ExportTemplateActivation::query()
                ->where('feature_key', $featureKey)
                ->where('output_format', $format->value)
                ->lockForUpdate()
                ->first();

            if (! $activation) {
                return false;
            }

            $version = $activation->version;

            $activation->delete();

            ExportTemplate::query()
                ->where('feature_key', $featureKey)
                ->where('output_format', $format->value)
                ->update(['is_active' => false]);

            ExportTemplateAuditLog::query()->create([
                'template_id' => $version?->template_id,
                'template_version_id' => $version?->id,
                'actor_id' => $actorId,
                'action' => ExportTemplateAuditLog::ACTION_DEACTIVATED,
                'metadata' => [
                    'feature_key' => $featureKey,
                    'output_format' => $format->value,
                ],
            ]);

            return true;
        }, 3);
    }
}
