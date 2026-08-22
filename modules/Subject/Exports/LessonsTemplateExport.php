<?php

namespace Modules\Subject\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LessonsTemplateExport implements WithMultipleSheets
{
    /**
     * Data sheet first so Excel::import() reads lesson rows by default
     */
    public function sheets(): array
    {
        return [
            new LessonsDataSheet,
            new LessonsInstructionsSheet,
        ];
    }
}
