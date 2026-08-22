<?php

namespace Modules\ExportTemplates\Contracts;

interface DocumentConverterInterface
{
    public function supports(string $sourceExtension, string $targetExtension): bool;

    /**
     * Trả về đường dẫn tuyệt đối tới tài liệu đã chuyển đổi.
     */
    public function convert(
        string $sourcePath,
        string $targetExtension,
        ?string $destinationPath = null
    ): string;
}
