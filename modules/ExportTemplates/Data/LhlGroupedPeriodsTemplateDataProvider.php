<?php

namespace Modules\ExportTemplates\Data;

/**
 * Dùng chung schema LHL động (group_1..group_9 và period_1..period_9), nhưng
 * có feature key riêng để mẫu chia nhóm tiết được Active độc lập với mẫu cũ.
 */
class LhlGroupedPeriodsTemplateDataProvider extends LhlTemplateDataProvider
{
    public function featureKey(): string
    {
        return 'lhl.training_plan.grouped_periods';
    }
}
