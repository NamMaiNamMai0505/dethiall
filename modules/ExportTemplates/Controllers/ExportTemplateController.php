<?php

namespace Modules\ExportTemplates\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ExportTemplates\Contracts\DocumentConverterInterface;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Exceptions\InvalidTemplateBindingException;
use Modules\ExportTemplates\Exceptions\InvalidTemplateException;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Models\ExportTemplateBinding;
use Modules\ExportTemplates\Models\ExportTemplateVersion;
use Modules\ExportTemplates\Requests\CloneExportTemplateRequest;
use Modules\ExportTemplates\Requests\CreateExportTemplatesRequest;
use Modules\ExportTemplates\Requests\CreateExportTemplateVersionRequest;
use Modules\ExportTemplates\Services\FeatureCatalog;
use Modules\ExportTemplates\Services\TemplateBindingService;
use Modules\ExportTemplates\Services\TemplateDataExplorer;
use Modules\ExportTemplates\Services\TemplateDataRegistry;
use Modules\ExportTemplates\Services\TemplateLifecycleService;
use Modules\ExportTemplates\Services\TemplatePreviewBuilder;
use Modules\ExportTemplates\Services\TemplateRenderService;
use Modules\ExportTemplates\Services\TemplateVariableCatalog;

/**
 * Mẫu xuất tách theo portal:
 * - dashboard → layout admin
 * - lms → layout LMS
 * - grades → layout grades
 * Scope cố định theo route, không up nhầm portal.
 */
class ExportTemplateController extends Controller
{
    public function __construct(
        private readonly TemplateLifecycleService $lifecycle,
        private readonly TemplateDataRegistry $dataRegistry,
        private readonly TemplateDataExplorer $dataExplorer,
        private readonly TemplateBindingService $bindingService,
        private readonly TemplatePreviewBuilder $previewBuilder,
        private readonly TemplateRenderService $renderService,
        private readonly FeatureCatalog $featureCatalog,
        private readonly TemplateVariableCatalog $variableCatalog, private readonly DocumentConverterInterface $documentConverter
    ) {
        $this->middleware(['auth']);
    }

    public function index(Request $request, string $portal = 'dashboard')
    {
        $this->assertPortal($portal);
        $this->assertCanView();

        $scope = $this->scopeFromPortal($portal);
        $query = ExportTemplate::query()
            ->where(function ($q) use ($scope) {
                $q->where('scope', $scope)->orWhere('scope', ExportTemplate::SCOPE_SHARED);
            })
            ->with('latestVersion')
            ->withCount('versions');

        $search = trim((string) $request->query('search'));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('feature_key', 'like', "%{$search}%");
            });
        }

        $format = OutputFormat::tryFrom((string) $request->query('format'));
        if ($format) {
            $query->where('output_format', $format->value);
        }

        $status = TemplateStatus::tryFrom((string) $request->query('status'));
        if ($status) {
            $query->where('status', $status->value);
        }

        $featureKey = trim((string) $request->query('feature_key'));
        if ($featureKey !== '') {
            $query->where('feature_key', $featureKey);
        }

        $templates = $query
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view($this->viewName($portal, 'index'), [
            'templates' => $templates,
            'portal' => $portal,
            'scope' => $scope,
            'portalLabel' => $this->portalLabel($portal),
            'featureKeys' => ExportTemplate::query()
                ->where(function ($q) use ($scope) {
                    $q->where('scope', $scope)->orWhere('scope', ExportTemplate::SCOPE_SHARED);
                })
                ->select('feature_key')
                ->distinct()
                ->orderBy('feature_key')
                ->pluck('feature_key'),
        ]);
    }

    public function create(string $portal = 'dashboard')
    {
        $this->assertPortal($portal);
        $this->assertCanCreate();

        return view($this->viewName($portal, 'create'), [
            'portal' => $portal,
            'scope' => $this->scopeFromPortal($portal),
            'portalLabel' => $this->portalLabel($portal),
            'featureHints' => $this->featureCatalog->forPortal($portal),
        ]);
    }

    public function variables(string $portal, string $featureKey)
    {
        $this->assertPortal($portal);
        $this->assertCanView();

        abort_unless(in_array($featureKey, $this->featureCatalog->forPortal($portal), true), 404);

        return response()->json([
            'feature_key' => $featureKey,
            'variables' => $this->variableCatalog->forFeature($featureKey),
        ]);
    }

    public function store(CreateExportTemplatesRequest $request, string $portal = 'dashboard')
    {
        $this->assertPortal($portal);

        $scope = $this->scopeFromPortal($portal);
        $featureKey = (string) $request->validated('feature_key');
        if (! $this->dataRegistry->has($featureKey)) {
            return back()
                ->withInput()
                ->with('error', "Feature [{$featureKey}] chưa có Data Provider. Hãy chọn một feature có sẵn trong danh sách.");
        }
        try {
            $template = $this->lifecycle->createTemplate(
                $request->safe()->except('file'),
                $request->file('file'),
                $scope,
                Auth::id()
            );
        } catch (InvalidTemplateException $exception) {
            return back()
                ->withInput()
                ->with('error', implode(' ', $exception->errors()) ?: $exception->getMessage());
        }

        return redirect()->route('export-templates.portal.show', [
            'portal' => $portal,
            'exportTemplate' => $template,
        ])->with('success', 'Đã tải template dưới dạng bản nháp. Hãy kiểm tra rồi kích hoạt phiên bản.');
    }

    public function show(string $portal, ExportTemplate $exportTemplate)
    {
        $this->assertPortal($portal);
        $this->assertCanView();
        $this->assertTemplateInPortal($exportTemplate, $portal);
        $exportTemplate->load([
            'creator',
            'latestVersion',
            'versions' => fn ($query) => $query
                ->with(['creator', 'activations'])
                ->withCount('bindings'),
            'auditLogs' => fn ($query) => $query
                ->with('actor')
                ->limit(30),
        ]);

        return view($this->viewName($portal, 'show'), [
            'template' => $exportTemplate,
            'portal' => $portal,
            'scope' => $this->scopeFromPortal($portal),
            'portalLabel' => $this->portalLabel($portal),
        ]);
    }

    public function destroy(string $portal, ExportTemplate $exportTemplate)
    {
        $this->assertPortal($portal);
        $this->assertCanDelete();
        $this->assertTemplateInPortal($exportTemplate, $portal);

        try {
            $this->lifecycle->archiveTemplate($exportTemplate, Auth::id());
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('export-templates.portal.index', ['portal' => $portal])
            ->with('success', 'Đã lưu trữ template. File được giữ lại để phục hồi khi cần.');
    }

    public function testExport(Request $request, string $portal, ExportTemplate $exportTemplate)
    {
        $this->assertPortal($portal);
        $this->assertCanView();
        $this->assertTemplateInPortal($exportTemplate, $portal);
        $format = $exportTemplate->output_format;
        abort_unless($format instanceof OutputFormat, 422, 'Template chưa có định dạng hợp lệ.');

        try {
            $path = $this->renderService->renderActiveWithMockData(
                $exportTemplate->feature_key,
                $format,
                Auth::id()
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Không thể render template: '.$exception->getMessage());
        }

        $extension = $format === OutputFormat::WORD ? 'docx' : 'xlsx';
        $downloadFormat = strtolower((string) $request->query('format', $extension));
        if ($downloadFormat === 'pdf') {
            try {
                $path = $this->documentConverter->convert($path, 'pdf');
                $extension = 'pdf';
            } catch (\Throwable $exception) {
                report($exception);

                return back()->with('error', 'Không thể chuyển preview sang PDF: '.$exception->getMessage());
            }
        }
        $name = Str::slug($exportTemplate->name).'-mock.'.$extension;

        return response()->download($path, $name)->deleteFileAfterSend(true);
    }

    public function createVersion(
        CreateExportTemplateVersionRequest $request,
        string $portal,
        ExportTemplate $exportTemplate
    ) {
        $this->assertPortal($portal);
        $this->assertTemplateInPortal($exportTemplate, $portal);

        try {
            $version = $this->lifecycle->createVersion(
                $exportTemplate,
                $request->file('file'),
                Auth::id()
            );
        } catch (\DomainException|\InvalidArgumentException|InvalidTemplateException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with(
            'success',
            "Đã tạo phiên bản {$version->version_number} ở trạng thái nháp."
        );
    }

    public function activate(
        string $portal,
        ExportTemplate $exportTemplate,
        ExportTemplateVersion $version
    ) {
        $this->assertPortal($portal);
        $this->assertCanEdit();
        $this->assertTemplateInPortal($exportTemplate, $portal);
        $this->assertVersionInTemplate($version, $exportTemplate);

        try {
            $this->lifecycle->activateVersion($exportTemplate, $version, Auth::id());
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with(
            'success',
            "Đã kích hoạt phiên bản {$version->version_number}."
        );
    }

    public function clone(
        CloneExportTemplateRequest $request,
        string $portal,
        ExportTemplate $exportTemplate
    ) {
        $this->assertPortal($portal);
        $this->assertTemplateInPortal($exportTemplate, $portal);

        try {
            $clone = $this->lifecycle->cloneTemplate(
                $exportTemplate,
                $request->validated('name'),
                Auth::id()
            );
        } catch (\DomainException|\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('export-templates.portal.show', [
            'portal' => $portal,
            'exportTemplate' => $clone,
        ])->with('success', 'Đã clone template thành một bản nháp độc lập.');
    }

    public function download(
        string $portal,
        ExportTemplate $exportTemplate,
        ExportTemplateVersion $version
    ) {
        $this->assertPortal($portal);
        $this->assertCanView();
        $this->assertTemplateInPortal($exportTemplate, $portal);
        $this->assertVersionInTemplate($version, $exportTemplate);

        $disk = Storage::disk($version->disk ?: 'local');
        abort_unless($disk->exists($version->file_path), 404, 'Không tìm thấy file template.');

        $this->lifecycle->recordDownload($exportTemplate, $version, Auth::id());

        $extension = $version->file_extension ?: 'bin';
        $downloadName = Str::slug($exportTemplate->name)
            .'-v'.$version->version_number.'.'.$extension;

        return $disk->download($version->file_path, $downloadName);
    }

    public function analyze(
        string $portal,
        ExportTemplate $exportTemplate,
        ExportTemplateVersion $version
    ) {
        $this->assertPortal($portal);
        $this->assertCanEdit();
        $this->assertTemplateInPortal($exportTemplate, $portal);
        $this->assertVersionInTemplate($version, $exportTemplate);

        try {
            $analyzed = $this->lifecycle->analyzeVersion(
                $exportTemplate,
                $version,
                Auth::id()
            );
        } catch (InvalidTemplateException|\RuntimeException $exception) {
            $message = $exception instanceof InvalidTemplateException
                ? (implode(' ', $exception->errors()) ?: $exception->getMessage())
                : $exception->getMessage();

            return back()->with('error', $message);
        }

        return back()->with(
            'success',
            'Đã phân tích version '.$analyzed->version_number
            .' bằng '.($analyzed->manifest['parser'] ?? 'parser mới').'.'
        );
    }

    public function bindingsEditor(
        Request $request,
        string $portal,
        ExportTemplate $exportTemplate,
        ExportTemplateVersion $version
    ) {
        $this->assertPortal($portal);
        $this->assertCanView();
        $this->assertTemplateInPortal($exportTemplate, $portal);
        $this->assertVersionInTemplate($version, $exportTemplate);

        if (! $this->dataRegistry->has($exportTemplate->feature_key)) {
            return redirect()
                ->route('export-templates.portal.show', compact('portal', 'exportTemplate'))
                ->with('error', "Feature [{$exportTemplate->feature_key}] chưa có Data Provider.");
        }

        $provider = $this->dataRegistry->get($exportTemplate->feature_key);
        $mockData = $provider->mockData();
        $groups = $this->dataExplorer->groups(
            $provider->schema(),
            (string) $request->query('q')
        );
        foreach ($groups as &$group) {
            foreach ($group['fields'] as &$field) {
                $field['preview_value'] = ($field['type'] ?? null) === 'collection'
                    ? count($mockData[$field['key']] ?? [])
                    : $this->dataExplorer->value($mockData, (string) $field['key']);
            }
            unset($field);
        }
        unset($group);

        $targetSearch = mb_strtolower(trim((string) $request->query('target_q')));
        $targets = array_values(array_filter(
            $version->manifest['targets'] ?? [],
            static function (array $target) use ($targetSearch): bool {
                if ($targetSearch === '') {
                    return true;
                }

                return str_contains(mb_strtolower(json_encode($target, JSON_UNESCAPED_UNICODE)), $targetSearch);
            }
        ));
        $bindings = $version->bindings()->get()->keyBy('target_ref');
        $selectedRef = (string) $request->query('target_ref');
        $selectedTarget = collect($targets)->firstWhere('ref', $selectedRef)
            ?? collect($targets)->first();
        $preview = $this->previewBuilder->build(
            $version,
            $provider,
            (string) $request->query('preview_sheet')
        );

        return view('exporttemplates::bindings', [
            'layout' => $this->portalLayout($portal),
            'portal' => $portal,
            'portalLabel' => $this->portalLabel($portal),
            'template' => $exportTemplate,
            'version' => $version->load('activations'),
            'groups' => $groups,
            'targets' => array_slice($targets, 0, 300),
            'targetTotal' => count($targets),
            'selectedTarget' => $selectedTarget,
            'bindings' => $bindings,
            'selectedBinding' => $selectedTarget
                ? $bindings->get($selectedTarget['ref'] ?? '')
                : null,
            'canEdit' => $this->canEditCurrentUser(),
            'preview' => $preview,
            'previewOnly' => $request->routeIs('export-templates.portal.versions.preview'),
        ]);
    }

    public function preview(
        Request $request,
        string $portal,
        ExportTemplate $exportTemplate,
        ExportTemplateVersion $version
    ) {
        return $this->bindingsEditor($request, $portal, $exportTemplate, $version);
    }

    public function storeBinding(
        Request $request,
        string $portal,
        ExportTemplate $exportTemplate,
        ExportTemplateVersion $version
    ) {
        $this->assertPortal($portal);
        $this->assertCanEdit();
        $this->assertTemplateInPortal($exportTemplate, $portal);
        $this->assertVersionInTemplate($version, $exportTemplate);
        $validated = $request->validate([
            'target_ref' => ['required', 'string', 'max:255'],
            'data_key' => ['required', 'string', 'max:255'],
            'formatter' => ['nullable', 'string', 'max:128'],
            'style' => ['nullable', 'array'],
            'style.font_name' => ['nullable', 'string', 'max:100'],
            'style.font_size' => ['nullable', 'numeric', 'between:6,72'],
            'style.bold' => ['nullable', 'boolean'],
            'style.italic' => ['nullable', 'boolean'],
            'style.align' => ['nullable', 'in:left,center,right,justify'],
            'style.vertical_align' => ['nullable', 'in:top,middle,bottom'],
            'style.width' => ['nullable', 'numeric', 'between:1,2000'],
            'style.height' => ['nullable', 'numeric', 'between:1,2000'],
            'style.border_style' => ['nullable', 'in:none,thin,medium,thick,dashed,dotted,double'],
            'style.border_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'style.padding' => ['nullable', 'numeric', 'between:0,200'],
            'style.margin' => ['nullable', 'numeric', 'between:0,200'],
            'options' => ['nullable', 'array'],
            'options.row_height' => ['nullable', 'numeric', 'between:1,1000'],
            'options.column_width' => ['nullable', 'numeric', 'between:1,1000'],
            'options.cell_action' => ['nullable', 'in:none,merge,split'],
            'options.merge_range' => ['nullable', 'string', 'max:32'],
        ]);

        try {
            $this->bindingService->bind(
                $version,
                $validated['target_ref'],
                $validated['data_key'],
                $validated['formatter'] ?? null,
                $validated['options'] ?? [],
                Auth::id(),
                $validated['style'] ?? []
            );
        } catch (InvalidTemplateBindingException $exception) {
            return back()->withInput()->with(
                'error',
                implode(' ', $exception->errors()) ?: $exception->getMessage()
            );
        }

        return back()->with('success', 'Đã cập nhật binding và dữ liệu preview mẫu.');
    }

    public function destroyBinding(
        string $portal,
        ExportTemplate $exportTemplate,
        ExportTemplateVersion $version,
        ExportTemplateBinding $binding
    ) {
        $this->assertPortal($portal);
        $this->assertCanEdit();
        $this->assertTemplateInPortal($exportTemplate, $portal);
        $this->assertVersionInTemplate($version, $exportTemplate);

        try {
            $this->bindingService->remove($version, $binding, Auth::id());
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã gỡ binding khỏi target.');
    }

    /** Redirect legacy /export-templates?scope= → portal path */
    public function legacyIndex(Request $request)
    {
        $scope = $request->query('scope', 'dashboard');
        $portal = match ($scope) {
            'lms' => 'lms',
            'grades' => 'grades',
            default => 'dashboard',
        };

        return redirect()->route('export-templates.portal.index', ['portal' => $portal]);
    }

    protected function assertPortal(string $portal): void
    {
        abort_unless(in_array($portal, ['dashboard', 'lms', 'grades'], true), 404);
    }

    protected function assertCanView(): void
    {
        $u = Auth::user();
        abort_unless(
            $u && ($u->can('export-templates.index') || $u->isSuperAdmin() || $u->isManager() || $u->isInstructor()),
            403
        );
    }

    protected function assertCanCreate(): void
    {
        $u = Auth::user();
        abort_unless(
            $u && ($u->can('export-templates.create') || $u->isSuperAdmin() || $u->isManager()),
            403
        );
    }

    protected function assertCanEdit(): void
    {
        abort_unless($this->canEditCurrentUser(), 403);
    }

    protected function assertCanDelete(): void
    {
        $u = Auth::user();
        abort_unless(
            $u && ($u->can('export-templates.delete') || $u->isSuperAdmin()),
            403
        );
    }

    protected function assertTemplateInPortal(ExportTemplate $t, string $portal): void
    {
        $scope = $this->scopeFromPortal($portal);
        abort_unless(
            $t->scope === $scope || $t->scope === ExportTemplate::SCOPE_SHARED,
            404
        );
    }

    protected function assertVersionInTemplate(
        ExportTemplateVersion $version,
        ExportTemplate $template
    ): void {
        abort_unless((int) $version->template_id === (int) $template->id, 404);
    }

    protected function scopeFromPortal(string $portal): string
    {
        return match ($portal) {
            'lms' => ExportTemplate::SCOPE_LMS,
            'grades' => ExportTemplate::SCOPE_GRADES,
            default => ExportTemplate::SCOPE_DASHBOARD,
        };
    }

    protected function portalLabel(string $portal): string
    {
        return match ($portal) {
            'lms' => 'LMS',
            'grades' => 'Quản lý điểm',
            default => 'Dashboard',
        };
    }

    protected function viewName(string $portal, string $page): string
    {
        // grades::templates.index | lms shared exporttemplates::portal.* | dashboard admin
        return "exporttemplates::portals.{$portal}.{$page}";
    }

    protected function portalLayout(string $portal): string
    {
        return match ($portal) {
            'lms' => 'layouts.lms-learner',
            'grades' => 'layouts.grades',
            default => 'layouts.admin',
        };
    }

    protected function canEditCurrentUser(): bool
    {
        $user = Auth::user();

        return (bool) ($user && (
            $user->can('export-templates.edit')
            || $user->isSuperAdmin()
            || $user->isManager()
        ));
    }

    /** @return list<string> */
    protected function featureHints(string $portal): array
    {
        return match ($portal) {
            'lms' => ['lhl.training_plan', 'lhl.training_plan.grouped_periods', 'lms.course_list', 'lms.gradebook', 'lms.attendance'],
            'grades' => ['grades.score_sheet', 'grades.summary', 'grades.transcript'],
            default => ['lhl.training_plan', 'lhl.training_plan.grouped_periods', 'dashboard.stats', 'dashboard.schedule', 'dashboard.report'],
        };
    }
}
