<?php

namespace Modules\StandardHours\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\StandardHours\Models\StandardHoursSetting;
use Modules\StandardHours\Services\PeriodService;
use Modules\StandardHours\Services\ResearchDistributionService;

class StandardHoursSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ResearchDistributionService::class);
        $service->saveRules($service->defaultRules(), User::query()->value('id'));

        if (! StandardHoursSetting::query()->where('key', StandardHoursSetting::KEY_PERIOD_MODE)->exists()) {
            app(PeriodService::class)->setMode(
                PeriodService::MODE_CALENDAR_YEAR,
                User::query()->value('id')
            );
        }
    }
}
