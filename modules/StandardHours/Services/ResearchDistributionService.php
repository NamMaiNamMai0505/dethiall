<?php

namespace Modules\StandardHours\Services;

use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Models\StandardHoursSetting;

class ResearchDistributionService
{
    /**
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        $setting = StandardHoursSetting::query()
            ->where('key', StandardHoursSetting::KEY_RESEARCH_DISTRIBUTION)
            ->first();

        if ($setting?->value) {
            return $setting->value;
        }

        return $this->defaultRules();
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultRules(): array
    {
        return [
            'single' => ['lead' => 1.0],
            'two' => ['lead' => 2 / 3, 'member' => 1 / 3],
            'three' => ['lead' => 0.5, 'member' => 0.25],
            'four_plus' => [
                'lead_fixed' => 1 / 3,
                'use_contribution_percent' => true,
                'equal_split_remainder' => true,
            ],
        ];
    }

    public function saveRules(array $rules, ?int $userId = null): void
    {
        StandardHoursSetting::updateOrCreate(
            ['key' => StandardHoursSetting::KEY_RESEARCH_DISTRIBUTION],
            [
                'value' => $rules,
                'updated_by' => $userId,
            ]
        );
    }

    /**
     * Tính phần giờ của chính người kê khai mà không yêu cầu khai danh sách
     * những người còn lại trong sản phẩm.
     */
    public function calculatePersonalHours(
        float $annualProductHours,
        int $memberCount,
        string $participationType,
        ?float $contributionPercent = null
    ): float {
        $memberCount = max(1, $memberCount);
        $rules = $this->getRules();

        if ($memberCount === 1) {
            return round($annualProductHours * (float) ($rules['single']['lead'] ?? 1), 2);
        }

        if ($participationType === ResearchRecord::PARTICIPATION_LEAD) {
            $ratio = match (true) {
                $memberCount === 2 => (float) ($rules['two']['lead'] ?? (2 / 3)),
                $memberCount === 3 => (float) ($rules['three']['lead'] ?? 0.5),
                default => (float) ($rules['four_plus']['lead_fixed'] ?? (1 / 3)),
            };

            return round($annualProductHours * $ratio, 2);
        }

        if ($contributionPercent !== null) {
            $ratio = min(100, max(0, $contributionPercent)) / 100;

            return round($annualProductHours * $ratio, 2);
        }

        $ratio = match (true) {
            $memberCount === 2 => (float) ($rules['two']['member'] ?? (1 / 3)),
            $memberCount === 3 => (float) ($rules['three']['member'] ?? 0.25),
            default => (
                1 - (float) ($rules['four_plus']['lead_fixed'] ?? (1 / 3))
            ) / max(1, $memberCount - 1),
        };

        return round($annualProductHours * max(0, $ratio), 2);
    }

    /**
     * @param  array<int, array{participation_type: string, contribution_percent?: float|null}>  $members
     * @return array<int, float>
     */
    public function distributeHours(float $totalHours, array $members): array
    {
        $count = count($members);

        if ($count === 0) {
            return [];
        }

        $rules = $this->getRules();
        $shares = array_fill(0, $count, 0.0);

        if ($count === 1) {
            $shares[0] = $totalHours * ($rules['single']['lead'] ?? 1);

            return $this->roundShares($shares);
        }

        if ($count === 2) {
            foreach ($members as $index => $member) {
                $ratio = $member['participation_type'] === ResearchRecord::PARTICIPATION_LEAD
                    ? ($rules['two']['lead'] ?? 2 / 3)
                    : ($rules['two']['member'] ?? 1 / 3);
                $shares[$index] = $totalHours * $ratio;
            }

            return $this->roundShares($shares);
        }

        if ($count === 3) {
            foreach ($members as $index => $member) {
                $ratio = $member['participation_type'] === ResearchRecord::PARTICIPATION_LEAD
                    ? ($rules['three']['lead'] ?? 0.5)
                    : ($rules['three']['member'] ?? 0.25);
                $shares[$index] = $totalHours * $ratio;
            }

            return $this->roundShares($shares);
        }

        $fourPlus = $rules['four_plus'] ?? [];
        $leadFixed = (float) ($fourPlus['lead_fixed'] ?? (1 / 3));
        $leadIndexes = [];
        foreach ($members as $index => $member) {
            if ($member['participation_type'] === ResearchRecord::PARTICIPATION_LEAD) {
                $leadIndexes[] = $index;
            }
        }

        if ($leadIndexes === []) {
            $leadIndexes[] = 0;
        }

        $leadShareEach = ($totalHours * $leadFixed) / count($leadIndexes);
        foreach ($leadIndexes as $leadIndex) {
            $shares[$leadIndex] += $leadShareEach;
        }

        $remainder = max(0, $totalHours - array_sum($shares));
        $contributions = array_map(
            fn ($member) => isset($member['contribution_percent']) ? (float) $member['contribution_percent'] : null,
            $members
        );
        $hasContributions = collect($contributions)->every(fn ($value) => $value !== null && $value > 0);
        $contributionSum = array_sum(array_map(fn ($v) => (float) ($v ?? 0), $contributions));

        if ($hasContributions && $contributionSum > 0) {
            foreach ($members as $index => $member) {
                $shares[$index] += $remainder * ((float) $member['contribution_percent'] / $contributionSum);
            }
        } else {
            $perMember = $remainder / $count;
            foreach ($members as $index => $_member) {
                $shares[$index] += $perMember;
            }
        }

        return $this->roundShares($shares);
    }

    /**
     * @param  array<int, float>  $shares
     * @return array<int, float>
     */
    private function roundShares(array $shares): array
    {
        return array_map(fn ($value) => round($value, 2), $shares);
    }
}
