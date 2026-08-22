<?php

namespace Modules\ExportTemplates\Data;

class GradeTranscriptDataProvider extends GradeScoreSheetDataProvider
{
    public function featureKey(): string
    {
        return 'grades.transcript';
    }
}
