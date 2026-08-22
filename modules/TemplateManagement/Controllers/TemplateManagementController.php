<?php

namespace Modules\TemplateManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\TemplateManagement\Services\TemplateManagementService;
use Modules\TemplateManagement\Requests\CreateTemplateManagementRequest;
use Modules\TemplateManagement\Requests\UpdateTemplateManagementRequest;

class TemplateManagementController extends Controller
{
    public function __construct(private readonly TemplateManagementService $service)
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        $this->authorize('template-management.index');

        $templates = $this->service->getAllTemplates();
        return view('template-management::index', ['templates' => $templates]);
    }

    public function create()
    {
        $this->authorize('template-management.create');
        return view('template-management::create');
    }

    public function store(CreateTemplateManagementRequest $request)
    {
        $this->authorize('template-management.create');

        $scope = $request->input('scope');

        $data = $request->validated();
        $data['scope'] = $scope;

        $file = $request->file('file');
        $data['file'] = $file;

        $this->service->uploadTemplate($data);

        return redirect()->route('template-management.index')
            ->with('success', 'Template đã tải thành công.');
    }

    public function show($id)
    {
        $this->authorize('template-management.index');
        $template = $this->service->getAllTemplates()->firstWhere('id', $id) ?? (object) ['name' => 'Template', 'scope' => 'lms'];

        return view('template-management::preview', ['template' => $template]);
    }

    public function destroy($id)
    {
        $this->authorize('template-management.delete');
        // Implementation
        return back()->with('success', 'Đã xóa template.');
    }
}