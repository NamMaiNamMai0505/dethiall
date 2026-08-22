<?php

namespace Modules\ExportTemplates\Enums;

enum TemplateBindingType: string
{
    case SCALAR = 'scalar';
    case DATE = 'date';
    case NUMBER = 'number';
    case IMAGE = 'image';
    case REPEATING_ROW = 'repeating_row';
    case TABLE = 'table';
    case GROUPED_TABLE = 'grouped_table';
}
