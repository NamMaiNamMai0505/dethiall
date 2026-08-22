<?php

namespace Modules\ExportTemplates\Services;

use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Models\ExportTemplateAuditLog;

class TemplateRenderService
{
    public function __construct(
        private readonly ActiveTemplateResolver $activeResolver,
        private readonly TemplateDataRegistry $dataRegistry,
        private readonly TemplateEngineRegistry $engineRegistry
    ) {}

    /**
     * Render bằng dữ liệu thật từ provider.
     *
     * @param  array<string, mixed>  $context
     */
    public function renderActive(
        string $featureKey,
        OutputFormat $format,
        array $context,
        ?int $actorId = null
    ): string {
        $provider = $this->dataRegistry->get($featureKey);

        return $this->render(
            $featureKey,
            $format,
            $provider->load($context),
            $actorId,
            false
        );
    }

    public function renderActiveWithMockData(
        string $featureKey,
        OutputFormat $format,
        ?int $actorId = null
    ): string {
        $provider = $this->dataRegistry->get($featureKey);

        return $this->render(
            $featureKey,
            $format,
            $provider->mockData(),
            $actorId,
            true
        );
    }

    private function render(
        string $featureKey,
        OutputFormat $format,
        array $data,
        ?int $actorId,
        bool $mock
    ): string {
        $startedAt = hrtime(true);
        $version = $this->activeResolver->resolve($featureKey, $format);
        $path = $this->engineRegistry->get($format)->render($version, $data);
        $durationMs = round((hrtime(true) - $startedAt) / 1_000_000, 2);

        ExportTemplateAuditLog::query()->create([
            'template_id' => $version->template_id,
            'template_version_id' => $version->id,
            'actor_id' => $actorId,
            'action' => ExportTemplateAuditLog::ACTION_RENDERED,
            'metadata' => [
                'feature_key' => $featureKey,
                'output_format' => $format->value,
                'mock_data' => $mock,
                'output_name' => basename($path),
                'duration_ms' => $durationMs,
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ],
        ]);

        return $path;
    }
}
