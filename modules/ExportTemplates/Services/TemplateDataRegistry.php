<?php

namespace Modules\ExportTemplates\Services;

use Modules\ExportTemplates\Contracts\TemplateDataProviderInterface;
use Modules\ExportTemplates\Exceptions\TemplateDataProviderNotFoundException;

class TemplateDataRegistry
{
    /**
     * @var array<string, TemplateDataProviderInterface>
     */
    private array $providers = [];

    /**
     * @param  iterable<TemplateDataProviderInterface>  $providers
     */
    public function __construct(iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    public function register(TemplateDataProviderInterface $provider): void
    {
        $featureKey = trim($provider->featureKey());

        if ($featureKey === '') {
            throw new \InvalidArgumentException('Template data provider phải có feature key.');
        }

        if (isset($this->providers[$featureKey])) {
            throw new \LogicException(
                "Template data provider cho [{$featureKey}] đã được đăng ký."
            );
        }

        $this->providers[$featureKey] = $provider;
    }

    public function has(string $featureKey): bool
    {
        return isset($this->providers[$featureKey]);
    }

    public function get(string $featureKey): TemplateDataProviderInterface
    {
        if (! $this->has($featureKey)) {
            throw new TemplateDataProviderNotFoundException(
                "Không tìm thấy TemplateDataProvider cho [{$featureKey}]."
            );
        }

        return $this->providers[$featureKey];
    }

    /**
     * @return array<string, TemplateDataProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }
}
