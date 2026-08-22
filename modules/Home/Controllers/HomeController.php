<?php

namespace Modules\Home\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('home::index');
    }
}
