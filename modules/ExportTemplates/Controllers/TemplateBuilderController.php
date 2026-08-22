<?php

namespace Modules\ExportTemplates\Controllers;

use App\Http\Controllers\Controller;
use App\Support\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\ExportTemplates\Models\ExportTemplateVersion;
use Modules\ExportTemplates\Services\BuilderTemplateService;
use Modules\ExportTemplates\Services\FeatureCatalog;
use Modules\ExportTemplates\Services\TemplateDataRegistry;
use Modules\ExportTemplates\Services\TemplateVariableCatalog;

class TemplateBuilderController extends Controller
{
    public function __construct(
        private readonly BuilderTemplateService $builder,
        private readonly FeatureCatalog $features,
        private readonly TemplateDataRegistry $registry,
        private readonly TemplateVariableCatalog $variables,
    ) {
        $this->middleware('auth');
    }

    public function create(string $portal)
    {
        $this->assertPortal($portal);
        $this->assertCanCreate();

        return view('exporttemplates::builder.create', [
            'portal' => $portal,
            'portalLabel' => $this->portalLabel($portal),
            'featureHints' => $this->features->forPortal($portal),
            'defaultOutputFormat' => (string) SystemSettings::get('shared', 'default_export_format', 'excel'),
        ]);
    }

    public function store(Request $request, string $portal)
    {
        $this->assertPortal($portal);
        $this->assertCanCreate();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'feature_key' => ['required', 'string', 'max:128'],
            'output_format' => ['required', 'in:word,excel'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        abort_unless(in_array($data['feature_key'], $this->features->forPortal($portal), true), 422);

        $version = $this->builder->create(
            $data['name'], $this->scope($portal), $data['feature_key'], $data['output_format'],
            (int) Auth::id(), $data['description'] ?? null
        );

        return redirect()->route('export-templates.portal.builder.edit', [
            'portal' => $portal, 'version' => $version,
        ])->with('success', 'Đã tạo Builder Template bản nháp.');
    }

    public function edit(string $portal, ExportTemplateVersion $version)
    {
        $this->assertPortal($portal);
        $this->assertCanView();
        $version->load(['template', 'builderDocument']);
        abort_unless($version->template && $version->template->scope === $this->scope($portal), 404);

        return view('exporttemplates::builder.edit', [
            'portal' => $portal,
            'portalLabel' => $this->portalLabel($portal),
            'version' => $version,
            'schema' => $version->builderDocument?->schema ?? [],
            'variables' => $this->variables->forFeature($version->template->feature_key),
            'editable' => $this->canEdit(),
        ]);
    }

    public function update(Request $request, string $portal, ExportTemplateVersion $version)
    {
        $this->assertPortal($portal);
        $this->assertCanEdit();
        $version->load('template');
        abort_unless($version->template && $version->template->scope === $this->scope($portal), 404);

        $schema = $request->input('schema');
        if (is_string($schema)) {
            try {
                $schema = json_decode($schema, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw ValidationException::withMessages([
                    'schema' => 'Cấu trúc template không phải JSON hợp lệ.',
                ]);
            }
            $request->merge(['schema' => $schema]);
        }
        $data = $request->validate(['schema' => ['required', 'array']]);
        $this->builder->save($version, $data['schema'], (int) Auth::id());

        return back()->with('success', 'Đã lưu cấu trúc Builder Template.');
    }

    public function newVersion(string $portal, ExportTemplateVersion $version)
    {
        $this->assertPortal($portal);
        $this->assertCanEdit();
        $version->load('template');
        abort_unless($version->template && $version->template->scope === $this->scope($portal), 404);
        $newVersion = $this->builder->createVersion($version, (int) Auth::id());

        return redirect()->route('export-templates.portal.builder.edit', ['portal' => $portal, 'version' => $newVersion])
            ->with('success', 'Đã tạo version Builder mới ở trạng thái bản nháp.');
    }

    private function assertPortal(string $portal): void
    {
        abort_unless(in_array($portal, ['dashboard', 'lms', 'grades'], true), 404);
    }

    private function scope(string $portal): string
    {
        return $portal === 'grades' ? 'grades' : $portal;
    }

    private function portalLabel(string $portal): string
    {
        return ['dashboard' => 'Dashboard', 'lms' => 'LMS', 'grades' => 'Quản lý điểm'][$portal];
    }

    private function canEdit(): bool
    {
        $user = Auth::user();
        return (bool) ($user && ($user->can('export-templates.edit') || $user->isSuperAdmin() || $user->isManager()));
    }

    private function assertCanView(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->can('export-templates.view') || $user->can('export-templates.create') || $user->isSuperAdmin() || $user->isManager()), 403);
    }

    private function assertCanCreate(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->can('export-templates.create') || $user->isSuperAdmin() || $user->isManager()), 403);
    }

    private function assertCanEdit(): void
    {
        abort_unless($this->canEdit(), 403);
    }
}
