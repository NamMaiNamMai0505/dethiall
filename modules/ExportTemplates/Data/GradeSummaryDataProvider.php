<?php

namespace Modules\ExportTemplates\Data;

class GradeSummaryDataProvider extends GradeScoreSheetDataProvider
{
    public function featureKey(): string
    {
        return 'grades.summary';
    }
}
