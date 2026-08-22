<?php

namespace Modules\User\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UsersTemplateExport implements WithMultipleSheets
{
    /**
     * Return array of sheets
     */
    public function sheets(): array
    {
        return [
            new InstructionsSheet,
            new DataSheet,
            new StudentDataSheet,
            new UnitsSheet,
            new ClassesSheet,
            new PermissionsSheet,
        ];
    }
}
