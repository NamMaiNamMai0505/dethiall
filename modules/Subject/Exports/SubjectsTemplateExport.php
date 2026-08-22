<?php

namespace Modules\Subject\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SubjectsTemplateExport implements WithMultipleSheets
{
    /**
     * Return array of sheets
     */
    public function sheets(): array
    {
        // Data sheet first so Excel::import() reads subject rows by default
        return [
            new DataSheet,
            new InstructionsSheet,
        ];
    }
}
