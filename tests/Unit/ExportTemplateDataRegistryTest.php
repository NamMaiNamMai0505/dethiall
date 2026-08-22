<?php

namespace Tests\Unit;

use Modules\ExportTemplates\Contracts\TemplateDataProviderInterface;
use Modules\ExportTemplates\Exceptions\TemplateDataProviderNotFoundException;
use Modules\ExportTemplates\Services\TemplateDataRegistry;
use PHPUnit\Framework\TestCase;

class ExportTemplateDataRegistryTest extends TestCase
{
    public function test_it_registers_and_resolves_allowlisted_provider(): void
    {
        $provider = $this->provider('lhl.training_plan');
        $registry = new TemplateDataRegistry([$provider]);

        $this->assertTrue($registry->has('lhl.training_plan'));
        $this->assertSame($provider, $registry->get('lhl.training_plan'));
        $this->assertSame(['class' => ['name' => ['type' => 'string']]], $provider->schema());
    }

    public function test_it_rejects_duplicate_feature_provider(): void
    {
        $registry = new TemplateDataRegistry([$this->provider('lhl.training_plan')]);

        $this->expectException(\LogicException::class);

        $registry->register($this->provider('lhl.training_plan'));
    }

    public function test_it_reports_missing_provider(): void
    {
        $registry = new TemplateDataRegistry;

        $this->expectException(TemplateDataProviderNotFoundException::class);

        $registry->get('grades.score_sheet');
    }

    private function provider(string $featureKey): TemplateDataProviderInterface
    {
        return new class($featureKey) implements TemplateDataProviderInterface
        {
            public function __construct(private readonly string $key) {}

            public function featureKey(): string
            {
                return $this->key;
            }

            public function schema(): array
            {
                return ['class' => ['name' => ['type' => 'string']]];
            }

            public function mockData(): array
            {
                return ['class' => ['name' => 'Y54']];
            }

            public function load(array $context): array
            {
                return ['class' => ['name' => $context['class_name'] ?? 'Y54']];
            }
        };
    }
}
