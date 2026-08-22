<?php

namespace Modules\ExportTemplates\Services;

use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Exceptions\ActiveTemplateNotFoundException;
use Modules\ExportTemplates\Models\ExportTemplateActivation;
use Modules\ExportTemplates\Models\ExportTemplateVersion;

class ActiveTemplateResolver
{
    public function find(
        string $featureKey,
        OutputFormat|string $format
    ): ?ExportTemplateVersion {
        $format = $this->normalizeFormat($format);

        $activation = ExportTemplateActivation::query()
            ->where('feature_key', $featureKey)
            ->where('output_format', $format->value)
            ->with(['version.template', 'version.bindings'])
            ->first();

        $version = $activation?->version;
        $template = $version?->template;

        if (! $version || ! $template) {
            return null;
        }

        if (
            in_array($version->status, [TemplateStatus::ARCHIVED, TemplateStatus::INVALID], true)
            || in_array($template->status, [TemplateStatus::ARCHIVED, TemplateStatus::INVALID], true)
        ) {
            return null;
        }

        return $version;
    }

    public function resolve(
        string $featureKey,
        OutputFormat|string $format
    ): ExportTemplateVersion {
        $format = $this->normalizeFormat($format);
        $version = $this->find($featureKey, $format);

        if (! $version) {
            throw new ActiveTemplateNotFoundException(
                "Không có template active cho [{$featureKey}] định dạng [{$format->value}]."
            );
        }

        return $version;
    }

    private function normalizeFormat(OutputFormat|string $format): OutputFormat
    {
        if ($format instanceof OutputFormat) {
            return $format;
        }

        return OutputFormat::tryFrom(strtolower(trim($format)))
            ?? throw new \InvalidArgumentException("Định dạng template [{$format}] không hợp lệ.");
    }
}
