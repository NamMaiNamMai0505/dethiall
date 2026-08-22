<?php

namespace Modules\ExportTemplates\Contracts;

use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Models\ExportTemplateVersion;

interface TemplateEngineInterface
{
    public function supports(OutputFormat $format): bool;

    /**
     * Trả về đường dẫn tuyệt đối tới tài liệu đã render.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>|null  $bindings
     */
    public function render(
        ExportTemplateVersion $version,
        array $data,
        ?array $bindings = null
    ): string;
}
