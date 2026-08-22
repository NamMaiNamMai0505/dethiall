<?php

namespace Modules\ExportTemplates\Contracts;

interface TemplateDataProviderInterface
{
    public function featureKey(): string;

    /**
     * Cây dữ liệu allowlist dùng cho Data Explorer.
     *
     * @return array<string, mixed>
     */
    public function schema(): array;

    /**
     * Dữ liệu mẫu phải có cùng cấu trúc với dữ liệu thật.
     *
     * @return array<string, mixed>
     */
    public function mockData(): array;

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function load(array $context): array;
}
