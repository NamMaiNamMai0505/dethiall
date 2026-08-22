<?php

namespace Modules\Authentication\Controllers;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Support\ViewErrorBag;

class AuthenticationBaseController extends BaseController
{
    /**
     * Get view data with default variables
     */
    protected function getViewData(array $data = []): array
    {
        return array_merge([
            'errors' => session()->get('errors', new ViewErrorBag()),
        ], $data);
    }

    /**
     * Create view response with default data
     */
    protected function view(string $view, array $data = [])
    {
        return view($view, $this->getViewData($data));
    }
}
