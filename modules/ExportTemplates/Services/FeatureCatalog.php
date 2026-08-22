<?php

namespace Modules\ExportTemplates\Services;

class FeatureCatalog
{
    public function __construct(private readonly TemplateDataRegistry $registry) {}

    public function forPortal(string $portal): array
    {
        $allowed = match ($portal) {
            'lms' => ['lhl.training_plan', 'lhl.training_plan.grouped_periods', 'lms.'],
            'grades' => ['grades.'],
            // Dashboard cũng là nơi xuất Lịch Huấn Luyện, vì vậy phải
            // hiển thị provider LHL bên cạnh các biểu mẫu Dashboard.
            default => ['lhl.training_plan', 'lhl.training_plan.grouped_periods', 'dashboard.'],
        };

        return array_values(array_map(
            fn ($provider) => $provider->featureKey(),
            array_filter($this->registry->all(), function ($provider) use ($allowed): bool {
                $key = $provider->featureKey();
                foreach ($allowed as $prefix) {
                    if ($key === $prefix || str_ends_with($prefix, '.') && str_starts_with($key, $prefix)) {
                        return true;
                    }
                }

                return false;
            })
        ));
    }
}
