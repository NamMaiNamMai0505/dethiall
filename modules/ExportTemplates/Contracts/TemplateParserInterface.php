<?php

namespace Modules\ExportTemplates\Contracts;

interface TemplateParserInterface
{
    public function supports(string $fileExtension): bool;

    /**
     * @return array<string, mixed>
     */
    public function parse(string $absolutePath): array;
}
