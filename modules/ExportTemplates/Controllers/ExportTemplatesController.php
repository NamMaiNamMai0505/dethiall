<?php

namespace Modules\ExportTemplates\Controllers;

use App\Http\Controllers\Controller;

class ExportTemplatesController extends Controller
{
    public function index()
    {
        return view(strtolower('ExportTemplates') . '::index');
    }
}