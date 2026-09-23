<?php

namespace Modules\ScientificResearch\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PermissionCheck;
use App\Support\SystemNotifier;
use App\Support\WordExportTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\ScientificResearch\Exports\ScientificResearchReportExport;
use Modules\ScientificResearch\Models\ScientificResearchAnnouncement;
use Modules\ScientificResearch\Models\ScientificResearchAuditLog;
use Modules\ScientificResearch\Models\ScientificResearchCouncil;
use Modules\ScientificResearch\Models\ScientificResearchCouncilMember;
use Modules\ScientificResearch\Models\ScientificResearchFile;
use Modules\ScientificResearch\Models\ScientificResearchFunding;
use Modules\ScientificResearch\Models\ScientificResearchPlan;
use Modules\ScientificResearch\Models\ScientificResearchProduct;
use Modules\ScientificResearch\Models\ScientificResearchRepositoryDocument;
use Modules\ScientificResearch\Models\ScientificResearchRegistration;
use Modules\ScientificResearch\Models\ScientificResearchRegistrationExtension;
use Modules\ScientificResearch\Models\ScientificResearchResult;
use Modules\ScientificResearch\Models\ScientificResearchStaffProfile;
use Modules\StandardHours\Models\ResearchCategory;
use Modules\StandardHours\Models\ResearchRecord;

class ScientificResearchController extends Controller
{
    public function index(Request $request): View
    {
        return $this->dashboard($request, 'dashboard');
    }

    public function portal(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));

        return view('scientific-research::portal', [
            'keyword' => $keyword,
            'openAnnouncements' => $this->openAnnouncements(),
            'myRegistrations' => ScientificResearchRegistration::with(['researchCategory', 'results'])
                ->where('user_id', $request->user()->id)
                ->latest()
                ->take(8)
                ->get(),
            'products' => ScientificResearchProduct::with('registration.researchCategory')
                ->when($keyword !== '', fn ($query) => $query->where(function ($q) use ($keyword): void {
                    $q->where('title', 'like', '%'.$keyword.'%')
                        ->orWhere('authors', 'like', '%'.$keyword.'%')
                        ->orWhere('description', 'like', '%'.$keyword.'%');
                }))
                ->latest()
                ->take(12)
                ->get(),
            'documents' => ScientificResearchRepositoryDocument::with('registration')
                ->when($keyword !== '', fn ($query) => $query->where(function ($q) use ($keyword): void {
                    $q->where('title', 'like', '%'.$keyword.'%')
                        ->orWhere('keywords', 'like', '%'.$keyword.'%')
                        ->orWhere('summary', 'like', '%'.$keyword.'%');
                }))
                ->latest()
                ->take(12)
                ->get(),
            'statuses' => $this->registrationStatuses(),
        ]);
    }

    public function announcements(Request $request): View
    {
        return $this->dashboard($request, 'announcements');
    }

    public function topics(Request $request): View
    {
        if ($request->route('review_queue')) {
            $request->merge(['review_queue' => '1']);
        }

        return $this->dashboard($request, 'topics');
    }

    public function results(Request $request): View
    {
        return $this->dashboard($request, 'results');
    }

    public function registrationRequests(Request $request): View
    {
        $keyword = trim((string) $request->query('q'));

        $registrations = ScientificResearchRegistration::query()
            ->with(['user.unit', 'researchCategory', 'announcement', 'members.user', 'files', 'extensionRequests.requester', 'extensionRequests.reviewer'])
            ->whereIn('status', [
                ScientificResearchRegistration::STATUS_NEEDS_REVISION,
                ScientificResearchRegistration::STATUS_APPROVED,
                ScientificResearchRegistration::STATUS_IN_PROGRESS,
                ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED,
            ])
            ->when(! PermissionCheck::can($request->user(), 'scientific-research.registrations.edit'), function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id);
            })
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($q) use ($keyword): void {
                    $q->where('project_code', 'like', '%'.$keyword.'%')
                        ->orWhere('title', 'like', '%'.$keyword.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$keyword.'%'));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $extensionOverview = ScientificResearchRegistration::query()
            ->with(['user.unit', 'researchCategory', 'extensionRequests.requester', 'extensionRequests.reviewer'])
            ->withCount([
                'extensionRequests',
                'extensionRequests as pending_extension_requests_count' => fn ($query) => $query->where('status', ScientificResearchRegistrationExtension::STATUS_PENDING),
                'extensionRequests as approved_extension_requests_count' => fn ($query) => $query->where('status', ScientificResearchRegistrationExtension::STATUS_APPROVED),
                'extensionRequests as rejected_extension_requests_count' => fn ($query) => $query->where('status', ScientificResearchRegistrationExtension::STATUS_REJECTED),
            ])
            ->whereHas('extensionRequests')
            ->when(! PermissionCheck::can($request->user(), 'scientific-research.registrations.edit'), function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id);
            })
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($q) use ($keyword): void {
                    $q->where('project_code', 'like', '%'.$keyword.'%')
                        ->orWhere('title', 'like', '%'.$keyword.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$keyword.'%'));
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(8, ['*'], 'extensions_page')
            ->withQueryString();

        return view('scientific-research::registration-requests', [
            'registrations' => $registrations,
            'extensionOverview' => $extensionOverview,
            'statuses' => $this->registrationStatuses(),
        ]);
    }

    public function reports(Request $request): View
    {
        return $this->dashboard($request, 'reports');
    }

    public function auditLogs(Request $request): View
    {
        return view('scientific-research::index', [
            ...$this->dashboardData($request, 'audit'),
            'auditLogs' => ScientificResearchAuditLog::with('user')
                ->latest()
                ->paginate(20, ['*'], 'audit_page')
                ->withQueryString(),
        ]);
    }

    public function staff(Request $request): View { return $this->dashboard($request, 'staff'); }
    public function plans(Request $request): View { return $this->dashboard($request, 'plans'); }
    public function councils(Request $request): View { return $this->dashboard($request, 'councils'); }
    public function funding(Request $request): View { return $this->dashboard($request, 'funding'); }
    public function products(Request $request): View { return $this->dashboard($request, 'products'); }
    public function repository(Request $request): View { return $this->dashboard($request, 'repository'); }

    private function dashboard(Request $request, string $section): View
    {
        $announcements = ScientificResearchAnnouncement::query()
            ->withCount('registrations')
            ->latest()
            ->paginate(8, ['*'], 'announcements_page');

        $registrations = ScientificResearchRegistration::query()
            ->with(['user.unit', 'researchCategory', 'announcement', 'results', 'members.user'])
            ->when(! $this->canManageRegistrations($request), fn (Builder $query) => $this->scopeRegistrationParticipation($query, $request))
            ->when($request->query('mine') === '1', fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($request->query('review_queue') === '1', fn ($query) => $query->whereIn('status', [
                ScientificResearchRegistration::STATUS_SUBMITTED,
                ScientificResearchRegistration::STATUS_UNIT_APPROVED,
                ScientificResearchRegistration::STATUS_UNDER_REVIEW,
                ScientificResearchRegistration::STATUS_NEEDS_REVISION,
                ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED,
            ]))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('research_category_id', $request->query('category_id')))
            ->when(trim((string) $request->query('q')) !== '', function ($query) use ($request): void {
                $keyword = trim((string) $request->query('q'));
                $query->where(function ($q) use ($keyword): void {
                    $q->where('project_code', 'like', '%'.$keyword.'%')
                        ->orWhere('title', 'like', '%'.$keyword.'%')
                        ->orWhere('content', 'like', '%'.$keyword.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$keyword.'%'));
                });
            })
            ->latest()
            ->paginate(12, ['*'], 'registrations_page')
            ->withQueryString();

        $results = ScientificResearchResult::query()
            ->with(['registration.user', 'registration.researchCategory'])
            ->when(! $this->canManageResults($request), fn (Builder $query) => $query->whereHas('registration', fn (Builder $registrationQuery) => $this->scopeRegistrationParticipation($registrationQuery, $request)))
            ->latest()
            ->paginate(12, ['*'], 'results_page')
            ->withQueryString();

        $stats = [
            'announcements' => ScientificResearchAnnouncement::count(),
            'open_announcements' => ScientificResearchAnnouncement::where('status', ScientificResearchAnnouncement::STATUS_OPEN)->count(),
            'registrations' => ScientificResearchRegistration::count(),
            'submitted_results' => ScientificResearchResult::count(),
            'in_progress' => ScientificResearchRegistration::whereIn('status', [
                ScientificResearchRegistration::STATUS_APPROVED,
                ScientificResearchRegistration::STATUS_IN_PROGRESS,
                ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED,
            ])->count(),
            'completed' => ScientificResearchRegistration::where('status', ScientificResearchRegistration::STATUS_COMPLETED)->count(),
            'staff' => ScientificResearchStaffProfile::count(),
            'plans' => ScientificResearchPlan::count(),
            'councils' => ScientificResearchCouncil::count(),
            'funding' => ScientificResearchFunding::sum('amount'),
            'products' => ScientificResearchProduct::count(),
            'documents' => ScientificResearchRepositoryDocument::count(),
        ];

        $statusSummary = ScientificResearchRegistration::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $categorySummary = ScientificResearchRegistration::query()
            ->join('research_categories', 'research_categories.id', '=', 'scientific_research_registrations.research_category_id')
            ->selectRaw('research_categories.code, research_categories.name, count(*) as total')
            ->groupBy('research_categories.code', 'research_categories.name')
            ->orderByDesc('total')
            ->get();
        $fundingTypeSummary = ScientificResearchFunding::query()
            ->selectRaw('type, sum(amount) as total_amount, count(*) as total')
            ->groupBy('type')
            ->orderBy('type')
            ->get();
        $productTypeSummary = ScientificResearchProduct::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->orderBy('type')
            ->pluck('total', 'type');
        $visibleFundingQuery = ScientificResearchFunding::query()
            ->when(! PermissionCheck::can($request->user(), 'scientific-research.funding.edit'), fn (Builder $query) => $query->whereHas('registration', fn (Builder $registrationQuery) => $this->scopeRegistrationParticipation($registrationQuery, $request)));
        $dataFundingYears = (clone $visibleFundingQuery)
            ->get(['spent_on', 'created_at'])
            ->map(fn ($item) => ($item->spent_on ?: $item->created_at)?->format('Y'))
            ->filter();
        $currentYear = (int) now()->format('Y');
        $fundingYearOptions = $dataFundingYears
            ->merge(range($currentYear + 1, $currentYear - 5))
            ->map(fn ($year) => (string) $year)
            ->unique()
            ->sortDesc()
            ->values();
        $selectedFundingYear = preg_match('/^\d{4}$/', (string) $request->query('funding_year'))
            ? (string) $request->query('funding_year')
            : '';
        $visibleFundingQuery->when($selectedFundingYear !== '', function (Builder $query) use ($selectedFundingYear): void {
            $query->where(function (Builder $dateQuery) use ($selectedFundingYear): void {
                $dateQuery
                    ->whereYear('spent_on', $selectedFundingYear)
                    ->orWhere(function (Builder $fallbackQuery) use ($selectedFundingYear): void {
                        $fallbackQuery
                            ->whereNull('spent_on')
                            ->whereYear('created_at', $selectedFundingYear);
                    });
            });
        });
        $fundingSummaryItems = (clone $visibleFundingQuery)->with('registration.user.unit')->get();
        $fundingTotalCount = $fundingSummaryItems->count();
        $fundingDetailsByRegistration = $fundingSummaryItems
            ->groupBy('registration_id')
            ->map(fn ($items) => $items
                ->sortByDesc(fn ($item) => ($item->spent_on ?: $item->created_at)?->timestamp ?? 0)
                ->values());
        $fundingProjects = $fundingDetailsByRegistration
            ->map(function ($items, $registrationId): object {
                $registration = $items->first()?->registration;
                $typeTotals = $items->groupBy('type')->map(fn ($rows) => (float) $rows->sum('amount'));

                $estimateAmount = (float) ($typeTotals['ESTIMATE'] ?? 0);
                $allocatedAmount = (float) ($typeTotals['ALLOCATED'] ?? 0);
                $spentAmount = (float) ($typeTotals['SPENT'] ?? 0);
                $paymentAmount = (float) ($typeTotals['PAYMENT'] ?? 0);
                $settlementAmount = (float) ($typeTotals['SETTLEMENT'] ?? 0);
                $settlementItems = $items->where('type', 'SETTLEMENT');
                $paymentItems = $items->where('type', 'PAYMENT');
                $spentItems = $items->where('type', 'SPENT');

                if ($settlementAmount > 0) {
                    $actualAmount = $settlementAmount;
                    $actualSource = 'Chốt kinh phí';
                    $actualOn = $settlementItems->map(fn ($item) => $item->spent_on ?: $item->created_at)->filter()->max();
                } elseif ($paymentAmount > 0) {
                    $actualAmount = $paymentAmount;
                    $actualSource = 'Đã thanh toán';
                    $actualOn = $paymentItems->map(fn ($item) => $item->spent_on ?: $item->created_at)->filter()->max();
                } else {
                    $actualAmount = $spentAmount;
                    $actualSource = 'Đã chi/phát sinh';
                    $actualOn = $spentItems->map(fn ($item) => $item->spent_on ?: $item->created_at)->filter()->max();
                }

                $usagePercent = $allocatedAmount > 0 ? round($actualAmount * 100 / $allocatedAmount, 1) : null;

                return (object) [
                    'registration_id' => $registrationId,
                    'registration' => $registration,
                    'estimate_amount' => $estimateAmount,
                    'allocated_amount' => $allocatedAmount,
                    'spent_amount' => $spentAmount,
                    'payment_amount' => $paymentAmount,
                    'settlement_amount' => $settlementAmount,
                    'actual_amount' => $actualAmount,
                    'actual_source' => $actualSource,
                    'actual_on' => $actualOn,
                    'usage_percent' => $usagePercent,
                    'is_over_budget' => $allocatedAmount > 0 && $actualAmount > $allocatedAmount,
                    'total_count' => $items->count(),
                    'type_totals' => $typeTotals,
                    'latest_on' => $items->map(fn ($item) => $item->spent_on ?: $item->created_at)->filter()->max(),
                ];
            })
            ->sortByDesc('actual_on')
            ->values();
        $fundingSettlementTotal = (float) $fundingSummaryItems
            ->filter(fn ($item) => $item->type === 'SETTLEMENT' && $item->registration !== null)
            ->sum('amount');

        return view('scientific-research::index', $this->dashboardData($request, $section) + [
            'announcements' => $announcements,
            'registrations' => $registrations,
            'results' => $results,
            'staffProfiles' => ScientificResearchStaffProfile::with('user.unit')->latest()->paginate(12, ['*'], 'staff_page')->withQueryString(),
            'staffProfilesByUser' => ScientificResearchStaffProfile::with('user.unit')
                ->whereNotNull('user_id')
                ->get()
                ->keyBy('user_id'),
            'plans' => ScientificResearchPlan::query()
                ->with(['registration.user.unit', 'registration.members.user.unit'])
                ->when(! PermissionCheck::can($request->user(), 'scientific-research.plans.edit'), function ($query) use ($request): void {
                    $query->whereHas('registration', function ($registrationQuery) use ($request): void {
                        $registrationQuery
                            ->where('user_id', $request->user()->id)
                            ->orWhereHas('members', fn ($memberQuery) => $memberQuery->where('user_id', $request->user()->id));
                    });
                })
                ->latest()
                ->paginate(12, ['*'], 'plans_page')
                ->withQueryString(),
            'councils' => ScientificResearchCouncil::query()
                ->with(['registration', 'members'])
                ->when(! PermissionCheck::can($request->user(), 'scientific-research.councils.edit'), fn (Builder $query) => $query->whereHas('registration', fn (Builder $registrationQuery) => $this->scopeRegistrationParticipation($registrationQuery, $request)))
                ->latest()
                ->paginate(12, ['*'], 'councils_page')
                ->withQueryString(),

            'fundingYearOptions' => $fundingYearOptions,
            'selectedFundingYear' => $selectedFundingYear,
            'fundingTotalCount' => $fundingTotalCount,
            'fundingSettlementTotal' => $fundingSettlementTotal,
            'fundingDetailsByRegistration' => $fundingDetailsByRegistration,
            'fundingProjects' => $fundingProjects,
            'products' => ScientificResearchProduct::query()
                ->with(['registration.user.unit', 'registration.researchCategory', 'registration.members.user.unit'])
                ->when(! $this->canManageScientificRecords($request, 'scientific-research.products'), fn (Builder $query) => $query->whereHas('registration', fn (Builder $registrationQuery) => $this->scopeRegistrationParticipation($registrationQuery, $request)))
                ->latest()
                ->paginate(12, ['*'], 'products_page')
                ->withQueryString(),
            'repositoryDocuments' => ScientificResearchRepositoryDocument::query()
                ->with(['registration.user.unit', 'registration.researchCategory', 'registration.members.user.unit'])
                ->when(! $this->canManageScientificRecords($request, 'scientific-research.repository'), fn (Builder $query) => $query->whereHas('registration', fn (Builder $registrationQuery) => $this->scopeRegistrationParticipation($registrationQuery, $request)))
                ->latest()
                ->paginate(12, ['*'], 'repository_page')
                ->withQueryString(),
        ]);
    }

    private function dashboardData(Request $request, string $section): array
    {
        $visibleRegistrations = ScientificResearchRegistration::query()
            ->when(! $this->canManageRegistrations($request), fn (Builder $query) => $this->scopeRegistrationParticipation($query, $request));
        $visibleRegistrationIds = (clone $visibleRegistrations)->pluck('id');

        $stats = [
            'announcements' => ScientificResearchAnnouncement::count(),
            'open_announcements' => ScientificResearchAnnouncement::where('status', ScientificResearchAnnouncement::STATUS_OPEN)->count(),
            'registrations' => (clone $visibleRegistrations)->count(),
            'submitted_results' => ScientificResearchResult::whereIn('registration_id', $visibleRegistrationIds)->count(),
            'in_progress' => (clone $visibleRegistrations)->whereIn('status', [
                ScientificResearchRegistration::STATUS_APPROVED,
                ScientificResearchRegistration::STATUS_IN_PROGRESS,
                ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED,
            ])->count(),
            'completed' => (clone $visibleRegistrations)->where('status', ScientificResearchRegistration::STATUS_COMPLETED)->count(),
            'staff' => ScientificResearchStaffProfile::count(),
            'plans' => ScientificResearchPlan::whereIn('registration_id', $visibleRegistrationIds)->count(),
            'councils' => ScientificResearchCouncil::whereIn('registration_id', $visibleRegistrationIds)->count(),
            'funding' => ScientificResearchFunding::whereIn('registration_id', $visibleRegistrationIds)->sum('amount'),
            'products' => ScientificResearchProduct::whereIn('registration_id', $visibleRegistrationIds)->count(),
            'documents' => ScientificResearchRepositoryDocument::whereIn('registration_id', $visibleRegistrationIds)->count(),
        ];

        $statusSummary = (clone $visibleRegistrations)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $categorySummary = (clone $visibleRegistrations)
            ->join('research_categories', 'research_categories.id', '=', 'scientific_research_registrations.research_category_id')
            ->selectRaw('research_categories.code, research_categories.name, count(*) as total')
            ->groupBy('research_categories.code', 'research_categories.name')
            ->orderByDesc('total')
            ->get();
        $fundingTypeSummary = ScientificResearchFunding::query()
            ->whereIn('registration_id', $visibleRegistrationIds)
            ->selectRaw('type, sum(amount) as total_amount, count(*) as total')
            ->groupBy('type')
            ->orderBy('type')
            ->get();
        $productTypeSummary = ScientificResearchProduct::query()
            ->whereIn('registration_id', $visibleRegistrationIds)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->orderBy('type')
            ->pluck('total', 'type');
        $extensionSummary = [
            'total' => ScientificResearchRegistrationExtension::whereIn('registration_id', $visibleRegistrationIds)->count(),
            'pending' => ScientificResearchRegistrationExtension::whereIn('registration_id', $visibleRegistrationIds)->where('status', ScientificResearchRegistrationExtension::STATUS_PENDING)->count(),
            'approved' => ScientificResearchRegistrationExtension::whereIn('registration_id', $visibleRegistrationIds)->where('status', ScientificResearchRegistrationExtension::STATUS_APPROVED)->count(),
            'rejected' => ScientificResearchRegistrationExtension::whereIn('registration_id', $visibleRegistrationIds)->where('status', ScientificResearchRegistrationExtension::STATUS_REJECTED)->count(),
        ];

        return [
            'categories' => $this->activeResearchCategories(),
            'openAnnouncements' => $this->openAnnouncements(),
            'stats' => $stats,
            'statusSummary' => $statusSummary,
            'categorySummary' => $categorySummary,
            'fundingTypeSummary' => $fundingTypeSummary,
            'productTypeSummary' => $productTypeSummary,
            'extensionSummary' => $extensionSummary,
            'statuses' => $this->registrationStatuses(),
            'section' => $section,
            'allRegistrations' => ScientificResearchRegistration::query()
                ->with(['user.unit', 'researchCategory', 'members.user.unit'])
                ->when(! $this->canManageRegistrations($request), fn (Builder $query) => $this->scopeRegistrationParticipation($query, $request))
                ->orderByDesc('id')
                ->get(['id', 'project_code', 'title', 'user_id', 'research_category_id', 'academic_year', 'implementation_year', 'start_date', 'end_date', 'budget']),
            'internalUsers' => $this->internalUsers(),
        ];
    }

    public function exportReportCsv()
    {
        $filename = 'bao-cao-nghien-cuu-khoa-hoc-'.now()->format('Ymd-His').'.csv';
        $this->audit('report.export.csv', null, 'Xuất báo cáo CSV');

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Mã đề tài', 'Tên đề tài', 'Người đăng ký', 'Danh mục', 'Trạng thái', 'Tiến độ', 'Kinh phí', 'Số sản phẩm', 'Số kết quả']);
            ScientificResearchRegistration::with(['user', 'researchCategory', 'results'])
                ->withCount('results')
                ->orderBy('project_code')
                ->chunk(200, function ($rows) use ($out): void {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->project_code,
                            $row->title,
                            $row->user?->name,
                            trim(($row->researchCategory?->code ?: '').' '.$row->researchCategory?->name),
                            $this->registrationStatuses()[$row->status] ?? $row->status,
                            (int) $row->progress_percent.'%',
                            (float) $row->budget,
                            ScientificResearchProduct::where('registration_id', $row->id)->count(),
                            $row->results_count,
                        ]);
                    }
                });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportReportExcel()
    {
        $this->audit('report.export.excel', null, 'Xuất báo cáo Excel');

        return Excel::download(
            new ScientificResearchReportExport($this->reportRows()),
            'bao-cao-nghien-cuu-khoa-hoc-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportReportWord()
    {
        $rows = $this->reportRows();
        $this->audit('report.export.word', null, 'Xuất báo cáo Word');

        return WordExportTemplate::download(
            'bao-cao-nghien-cuu-khoa-hoc-'.now()->format('Ymd-His').'.docx',
            ['title' => 'BÁO CÁO NGHIÊN CỨU KHOA HỌC'],
            function ($section) use ($rows): void {
                $section->addText('Ngày xuất: '.now()->format('d/m/Y H:i'), ['italic' => true]);
                $section->addTextBreak(1);
                WordExportTemplate::addSimpleTable($section, [
                    'STT',
                    'Mã đề tài',
                    'Tên đề tài',
                    'Người đăng ký',
                    'Danh mục',
                    'Trạng thái',
                    'Tiến độ',
                    'Kinh phí',
                ], $rows->map(function ($row, int $index): array {
                    return [
                        (string) ($index + 1),
                        (string) $row->project_code,
                        (string) $row->title,
                        (string) $row->user?->name,
                        trim(($row->researchCategory?->code ?: '').' '.$row->researchCategory?->name),
                        $this->registrationStatuses()[$row->status] ?? $row->status,
                        (int) $row->progress_percent.'%',
                        number_format((float) $row->budget, 0, ',', '.'),
                    ];
                })->all(), [450, 1000, 2200, 1400, 1500, 1100, 700, 900]);
            },
            true
        );
    }

    public function downloadFile(ScientificResearchFile $file): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($file->file_path), 404, 'Không tìm thấy file đính kèm.');

        return Storage::disk('public')->download($file->file_path, $file->file_name ?: basename($file->file_path));
    }

    public function createRegistration(): View
    {
        return view('scientific-research::registration-form', [
            'categories' => $this->activeResearchCategories(),
            'openAnnouncements' => $this->openAnnouncements(),
            'internalUsers' => $this->internalUsers(),
        ]);
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'closes_at' => ['nullable', 'date'],
            'status' => ['required', 'in:OPEN,CLOSED'],
            'template' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,txt', 'max:20480'],
        ]);

        if ($request->hasFile('template')) {
            $file = $request->file('template');
            $data['template_path'] = $file->store('scientific-research/templates', 'public');
            $data['template_name'] = $file->getClientOriginalName();
        }

        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $announcement = ScientificResearchAnnouncement::create($data);
        $this->audit('announcement.created', $announcement, $announcement->title, $data);

        return back()->with('success', 'Đã tạo thông báo nghiên cứu khoa học.');
    }

    public function updateAnnouncement(Request $request, ScientificResearchAnnouncement $announcement): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'closes_at' => ['nullable', 'date'],
            'status' => ['required', 'in:OPEN,CLOSED'],
            'template' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,txt', 'max:20480'],
        ]);

        if ($request->hasFile('template')) {
            if ($announcement->template_path) {
                Storage::disk('public')->delete($announcement->template_path);
            }

            $file = $request->file('template');
            $data['template_path'] = $file->store('scientific-research/templates', 'public');
            $data['template_name'] = $file->getClientOriginalName();
        }

        unset($data['template']);
        $data['updated_by'] = $request->user()->id;
        $announcement->update($data);
        $this->audit('announcement.updated', $announcement, $announcement->title, $data);

        return back()->with('success', 'Đã cập nhật thông báo.');
    }

    public function destroyAnnouncement(ScientificResearchAnnouncement $announcement): RedirectResponse
    {
        $announcement->delete();
        $this->audit('announcement.deleted', $announcement, $announcement->title);

        return back()->with('success', 'Đã xóa thông báo.');
    }

    public function storeRegistration(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'announcement_id' => ['nullable', 'exists:scientific_research_announcements,id'],
            'research_category_id' => ['required', 'exists:research_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'topic' => ['nullable', 'string', 'max:255'],
            'academic_year' => ['nullable', 'string', 'max:30'],
            'implementation_year' => ['nullable', 'integer', 'min:2000', 'max:2200'],
            'duration_years' => ['nullable', 'numeric', 'min:0.25', 'max:20'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'product_quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'participant_count' => ['required', 'integer', 'min:1', 'max:999'],
            'lead_contribution_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'member_user_ids' => ['nullable', 'array'],
            'member_user_ids.*' => ['nullable', 'exists:users,id'],
            'member_roles' => ['nullable', 'array'],
            'member_roles.*' => ['nullable', 'string', 'max:100'],
            'member_contribution_percents' => ['nullable', 'array'],
            'member_contribution_percents.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'files.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,zip,rar,txt', 'max:20480'],
        ]);

        $memberRows = $this->registrationMemberRows($request);
        $leadPercent = (float) ($data['lead_contribution_percent'] ?? 100);
        $this->validateMemberCount((int) $data['participant_count'], $memberRows);
        $this->validateContributionTotal($leadPercent, $memberRows);
        $participantCount = max((int) $data['participant_count'], count($memberRows) + 1);

        $registration = DB::transaction(function () use ($request, $data, $memberRows, $leadPercent, $participantCount): ScientificResearchRegistration {
            $registration = ScientificResearchRegistration::create([
                ...collect($data)->except('files', 'member_user_ids', 'member_roles', 'member_contribution_percents')->all(),
                'user_id' => $request->user()->id,
                'budget' => $data['budget'] ?? 0,
                'product_quantity' => $data['product_quantity'] ?? 1,
                'duration_years' => $data['duration_years'] ?? 1,
                'participant_count' => $participantCount,
                'lead_contribution_percent' => $leadPercent,
                'status' => ScientificResearchRegistration::STATUS_SUBMITTED,
                'registered_at' => now(),
                'submitted_at' => now(),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
            $registration->update([
                'project_code' => 'NCKH'.now()->format('Y').'-'.str_pad((string) $registration->id, 4, '0', STR_PAD_LEFT),
            ]);

            $registration->members()->createMany($memberRows);
            $this->storeFiles($request, 'files', 'registration', $registration->id);

            return $registration;
        });
        $this->audit('registration.created', $registration, $registration->title, $registration->only(['project_code', 'status']));

        return redirect()
            ->route('scientific-research.registrations.show', $registration)
            ->with('success', 'Đã gửi đăng ký nghiên cứu khoa học đến Chỉ huy đơn vị để duyệt bước 1; sau đó hồ sơ chuyển Ban Khoa học Quân sự tiếp nhận/thẩm định.');
    }

    public function showRegistration(Request $request, ScientificResearchRegistration $registration): View
    {
        $this->authorizeRegistrationVisible($request, $registration);
        $registration->load(['user.unit', 'researchCategory', 'announcement', 'members.user.unit', 'files', 'results.files', 'extensionRequests.requester', 'extensionRequests.reviewer']);

        return view('scientific-research::registration-show', [
            'registration' => $registration,
            'isReviewMode' => (bool) $request->route('review_mode') || $request->query('mode') === 'review',
            'categories' => $this->activeResearchCategories(),
            'openAnnouncements' => $this->openAnnouncements(),
            'statuses' => $this->registrationStatuses(),
            'internalUsers' => $this->internalUsers(),
        ]);
    }

    public function updateRegistrationStatus(Request $request, ScientificResearchRegistration $registration): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:PROPOSED,SUBMITTED,UNIT_APPROVED,UNDER_REVIEW,NEEDS_REVISION,APPROVED,IN_PROGRESS,EXTENSION_REQUESTED,ACCEPTANCE_PENDING,COMPLETED,REJECTED'],
            'review_note' => ['nullable', 'string'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'extended_until' => ['nullable', 'date'],
        ]);

        $currentStatus = $registration->status;
        $nextStatus = $data['status'];
        $allowed = $this->allowedStatusTransitions()[$currentStatus] ?? [];
        abort_if($nextStatus !== $registration->status && ! in_array($nextStatus, $allowed, true), 422, 'Trạng thái chuyển không đúng quy trình NCKH.');
        $this->authorizeRegistrationStatusChange($request, $nextStatus);

        $update = [
            ...$data,
            'updated_by' => $request->user()->id,
        ];

        if ($nextStatus === ScientificResearchRegistration::STATUS_UNIT_APPROVED) {
            $update['unit_review_note'] = $data['review_note'] ?? null;
            $update['unit_reviewed_by'] = $request->user()->id;
            $update['unit_reviewed_at'] = now();
        } else {
            $update['reviewed_by'] = $request->user()->id;
            $update['reviewed_at'] = now();
        }

        $registration->update($update);
        $this->audit('registration.status_updated', $registration, $registration->title, [
            'from' => $currentStatus,
            'to' => $data['status'],
            'progress_percent' => $data['progress_percent'] ?? null,
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái đăng ký.');
    }

    public function submitRegistrationRevision(Request $request, ScientificResearchRegistration $registration): RedirectResponse
    {
        $this->authorizeRegistrationOwnerOrEditor($request, $registration);
        abort_if($registration->status !== ScientificResearchRegistration::STATUS_NEEDS_REVISION, 422, 'Chỉ hồ sơ đang cần bổ sung mới gửi lại được.');

        $data = $request->validate([
            'revision_response_note' => ['required', 'string'],
        ]);

        $nextStatus = $registration->unit_reviewed_by
            ? ScientificResearchRegistration::STATUS_UNDER_REVIEW
            : ScientificResearchRegistration::STATUS_SUBMITTED;

        $registration->update([
            'status' => $nextStatus,
            'revision_response_note' => $data['revision_response_note'],
            'revision_submitted_at' => now(),
            'submitted_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        $this->audit('registration.revision_submitted', $registration, $registration->title, [
            'to' => $nextStatus,
        ]);

        return back()->with('success', 'Đã gửi lại hồ sơ bổ sung để tiếp tục thẩm định.');
    }

    public function requestRegistrationExtension(Request $request, ScientificResearchRegistration $registration): RedirectResponse
    {
        $this->authorizeRegistrationOwnerOrEditor($request, $registration);
        abort_unless(in_array($registration->status, [
            ScientificResearchRegistration::STATUS_APPROVED,
            ScientificResearchRegistration::STATUS_IN_PROGRESS,
        ], true), 422, 'Chỉ đề tài đã duyệt hoặc đang thực hiện mới được xin gia hạn.');

        $data = $request->validate([
            'extension_requested_until' => ['required', 'date'],
            'extension_request_note' => ['required', 'string'],
        ]);

        if ($registration->end_date && ! \Illuminate\Support\Carbon::parse($data['extension_requested_until'])->greaterThan($registration->end_date)) {
            throw ValidationException::withMessages([
                'extension_requested_until' => 'Ngày xin gia hạn phải sau ngày kết thúc hiện tại của đề tài.',
            ]);
        }

        $registration->update([
            'status' => ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED,
            'extension_requested_until' => $data['extension_requested_until'],
            'extension_request_note' => $data['extension_request_note'],
            'extension_previous_status' => $registration->status,
            'extension_requested_at' => now(),
            'extension_review_note' => null,
            'extension_reviewed_by' => null,
            'extension_reviewed_at' => null,
            'updated_by' => $request->user()->id,
        ]);

        $registration->extensionRequests()->create([
            'requested_until' => $data['extension_requested_until'],
            'request_note' => $data['extension_request_note'],
            'previous_status' => $registration->extension_previous_status,
            'requested_by' => $request->user()->id,
            'requested_at' => now(),
            'status' => ScientificResearchRegistrationExtension::STATUS_PENDING,
        ]);

        $this->audit('registration.extension_requested', $registration, $registration->title, [
            'requested_until' => $data['extension_requested_until'],
        ]);

        return back()->with('success', 'Đã gửi yêu cầu xin gia hạn đến bộ phận thẩm định.');
    }

    public function reviewRegistrationExtension(Request $request, ScientificResearchRegistration $registration): RedirectResponse
    {
        abort_unless(PermissionCheck::can($request->user(), 'scientific-research.registrations.agency-approve')
            || PermissionCheck::can($request->user(), 'scientific-research.registrations.status'), 403);
        abort_if($registration->status !== ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED, 422, 'Hồ sơ này không có yêu cầu gia hạn đang chờ duyệt.');

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'extension_review_note' => ['nullable', 'string'],
        ]);

        $previousStatus = in_array($registration->extension_previous_status, [
            ScientificResearchRegistration::STATUS_APPROVED,
            ScientificResearchRegistration::STATUS_IN_PROGRESS,
        ], true) ? $registration->extension_previous_status : ScientificResearchRegistration::STATUS_IN_PROGRESS;

        $update = [
            'status' => $previousStatus,
            'extension_review_note' => $data['extension_review_note'] ?? null,
            'extension_reviewed_by' => $request->user()->id,
            'extension_reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'updated_by' => $request->user()->id,
        ];

        if ($data['decision'] === 'approve') {
            $update['extended_until'] = $registration->extension_requested_until;
            $update['end_date'] = $registration->extension_requested_until;
        }

        $extension = $registration->extensionRequests()
            ->where('status', ScientificResearchRegistrationExtension::STATUS_PENDING)
            ->latest('requested_at')
            ->latest('id')
            ->first();

        if ($extension) {
            $extension->update([
                'status' => $data['decision'] === 'approve'
                    ? ScientificResearchRegistrationExtension::STATUS_APPROVED
                    : ScientificResearchRegistrationExtension::STATUS_REJECTED,
                'review_note' => $data['extension_review_note'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }

        $registration->update($update);

        $this->audit('registration.extension_reviewed', $registration, $registration->title, [
            'decision' => $data['decision'],
            'extended_until' => $data['decision'] === 'approve' ? $registration->extension_requested_until?->toDateString() : null,
        ]);

        return back()->with('success', $data['decision'] === 'approve' ? 'Đã duyệt yêu cầu gia hạn.' : 'Đã từ chối yêu cầu gia hạn.');
    }

    public function updateRegistrationExtension(Request $request, ScientificResearchRegistrationExtension $extension): RedirectResponse
    {
        $extension->load('registration');
        $this->authorizeRegistrationOwnerOrEditor($request, $extension->registration);

        $data = $request->validate([
            'requested_until' => ['required', 'date'],
            'request_note' => ['required', 'string'],
            'status' => ['nullable', 'in:PENDING,APPROVED,REJECTED'],
            'review_note' => ['nullable', 'string'],
        ]);

        $registration = $extension->registration;

        if ($registration->end_date && ! \Illuminate\Support\Carbon::parse($data['requested_until'])->greaterThan($registration->start_date ?: $registration->end_date->copy()->subDay())) {
            throw ValidationException::withMessages([
                'requested_until' => 'Ngày gia hạn phải hợp lệ so với thời gian đề tài.',
            ]);
        }

        $canReview = PermissionCheck::can($request->user(), 'scientific-research.registrations.agency-approve')
            || PermissionCheck::can($request->user(), 'scientific-research.registrations.status');
        $nextStatus = $canReview ? ($data['status'] ?? $extension->status) : $extension->status;

        $extension->update([
            'requested_until' => $data['requested_until'],
            'request_note' => $data['request_note'],
            'status' => $nextStatus,
            'review_note' => $canReview ? ($data['review_note'] ?? null) : $extension->review_note,
            'reviewed_by' => $canReview && $nextStatus !== ScientificResearchRegistrationExtension::STATUS_PENDING ? $request->user()->id : $extension->reviewed_by,
            'reviewed_at' => $canReview && $nextStatus !== ScientificResearchRegistrationExtension::STATUS_PENDING ? now() : $extension->reviewed_at,
        ]);

        if ($nextStatus === ScientificResearchRegistrationExtension::STATUS_PENDING) {
            $registration->update([
                'status' => ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED,
                'extension_requested_until' => $data['requested_until'],
                'extension_request_note' => $data['request_note'],
                'extension_previous_status' => $extension->previous_status ?: $registration->status,
                'extension_requested_at' => $extension->requested_at ?: now(),
                'updated_by' => $request->user()->id,
            ]);
        } elseif ($nextStatus === ScientificResearchRegistrationExtension::STATUS_APPROVED) {
            $registration->update([
                'extended_until' => $data['requested_until'],
                'end_date' => $data['requested_until'],
                'updated_by' => $request->user()->id,
            ]);
        }

        $this->audit('registration.extension_updated', $registration, $registration->title, [
            'requested_until' => $data['requested_until'],
            'status' => $nextStatus,
        ]);

        return back()->with('success', 'Đã cập nhật dòng gia hạn.');
    }

    public function destroyRegistrationExtension(Request $request, ScientificResearchRegistrationExtension $extension): RedirectResponse
    {
        $extension->load('registration');
        $registration = $extension->registration;
        $this->authorizeRegistrationOwnerOrEditor($request, $registration);

        $wasPending = $extension->status === ScientificResearchRegistrationExtension::STATUS_PENDING;
        $extension->delete();

        if ($wasPending && $registration->status === ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED) {
            $latestPending = $registration->extensionRequests()
                ->where('status', ScientificResearchRegistrationExtension::STATUS_PENDING)
                ->latest('requested_at')
                ->latest('id')
                ->first();

            if ($latestPending) {
                $registration->update([
                    'extension_requested_until' => $latestPending->requested_until,
                    'extension_request_note' => $latestPending->request_note,
                    'extension_previous_status' => $latestPending->previous_status,
                    'extension_requested_at' => $latestPending->requested_at,
                    'updated_by' => $request->user()->id,
                ]);
            } else {
                $registration->update([
                    'status' => in_array($registration->extension_previous_status, [
                        ScientificResearchRegistration::STATUS_APPROVED,
                        ScientificResearchRegistration::STATUS_IN_PROGRESS,
                    ], true) ? $registration->extension_previous_status : ScientificResearchRegistration::STATUS_IN_PROGRESS,
                    'extension_requested_until' => null,
                    'extension_request_note' => null,
                    'extension_previous_status' => null,
                    'extension_requested_at' => null,
                    'updated_by' => $request->user()->id,
                ]);
            }
        }

        $this->audit('registration.extension_deleted', $registration, $registration->title, [
            'requested_until' => $extension->requested_until?->toDateString(),
        ]);

        return back()->with('success', 'Đã xóa dòng gia hạn.');
    }

    public function syncRegistrationToStandardHours(Request $request, ScientificResearchRegistration $registration): RedirectResponse
    {
        abort_if($registration->status !== ScientificResearchRegistration::STATUS_COMPLETED, 422, 'Chỉ đồng bộ đề tài đã hoàn thành sang Giờ chuẩn GV.');
        abort_if($registration->standard_hours_research_record_id, 422, 'Đề tài này đã được đồng bộ sang Giờ chuẩn GV.');

        $registration->load(['user', 'researchCategory', 'members.user']);
        $instructorId = $registration->user?->instructor_id;
        abort_if(! $instructorId, 422, 'Người đăng ký chưa liên kết tài khoản giảng viên nên chưa thể đồng bộ giờ chuẩn.');

        $acceptanceDate = $registration->results()->latest('completed_on')->value('completed_on')
            ?: $registration->end_date
            ?: now()->toDateString();
        $hours = (float) ($registration->researchCategory?->research_hours ?? 0);
        $leadPercent = (float) ($registration->lead_contribution_percent ?? 100);
        $leadHours = round($hours * $leadPercent / 100, 2);
        $year = $registration->implementation_year ?: (int) date('Y', strtotime((string) $acceptanceDate));

        $record = ResearchRecord::create([
            'instructor_id' => $instructorId,
            'research_category_id' => $registration->research_category_id,
            'product_name' => $registration->title,
            'role' => 'Chủ nhiệm',
            'participation_type' => ResearchRecord::PARTICIPATION_LEAD,
            'publication_date' => $acceptanceDate,
            'publication_place' => 'Phân hệ NCKH',
            'acceptance_date' => $acceptanceDate,
            'year' => $year,
            'period_mode' => 'calendar_year',
            'member_count' => max(1, (int) $registration->participant_count),
            'duration_years' => max(1, (int) round((float) ($registration->duration_years ?? 1))),
            'annual_product_hours' => $hours,
            'calculated_hours' => $leadHours,
            'contribution_percent' => $leadPercent,
            'converted_hours' => $leadHours,
            'notes' => trim(($registration->project_code ?: '').' - Đồng bộ từ phân hệ NCKH'),
            'status' => ResearchRecord::STATUS_APPROVED,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $record->members()->create([
            'instructor_id' => $instructorId,
            'role' => 'Chủ nhiệm',
            'participation_type' => ResearchRecord::PARTICIPATION_LEAD,
            'contribution_percent' => $leadPercent,
            'converted_hours' => $leadHours,
            'is_declarant' => true,
            'sort_order' => 1,
        ]);

        foreach ($registration->members as $index => $member) {
            $memberInstructorId = $member->user?->instructor_id;
            if (! $memberInstructorId) {
                continue;
            }

            $memberPercent = (float) $member->contribution_percent;
            $record->members()->create([
                'instructor_id' => $memberInstructorId,
                'role' => $member->role ?: 'Thành viên',
                'participation_type' => ResearchRecord::PARTICIPATION_MEMBER,
                'contribution_percent' => $memberPercent,
                'converted_hours' => round($hours * $memberPercent / 100, 2),
                'is_declarant' => false,
                'sort_order' => $index + 2,
            ]);
        }

        $registration->update([
            'standard_hours_research_record_id' => $record->id,
            'updated_by' => $request->user()->id,
        ]);
        $this->audit('registration.synced_standard_hours', $registration, $registration->title, [
            'standard_hours_research_record_id' => $record->id,
            'converted_hours' => $leadHours,
        ]);

        return back()->with('success', 'Đã đồng bộ đề tài sang Giờ chuẩn GV.');
    }

    public function storeResult(Request $request, ScientificResearchRegistration $registration): RedirectResponse
    {
        $this->authorizeRegistrationParticipantOrManager($request, $registration);
        $data = $request->validate([
            'completed_on' => ['nullable', 'date'],
            'implementation_year' => ['required', 'integer', 'min:2000', 'max:2200'],
            'summary' => ['nullable', 'string'],
            'files.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,zip,rar,txt', 'max:20480'],
        ]);

        DB::transaction(function () use ($request, $registration, $data): void {
            $result = ScientificResearchResult::create([
                ...collect($data)->except('files')->all(),
                'registration_id' => $registration->id,
                'status' => 'SUBMITTED',
                'submitted_at' => now(),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $registration->update([
                'status' => ScientificResearchRegistration::STATUS_ACCEPTANCE_PENDING,
                'updated_by' => $request->user()->id,
            ]);

            $this->storeFiles($request, 'files', 'result', $result->id);
            $this->audit('result.created', $result, $registration->title, ['registration_id' => $registration->id]);
        });

        return back()->with('success', 'Đã nộp kết quả nghiên cứu khoa học.');
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'full_name' => ['required_without:user_id', 'nullable', 'string', 'max:255'],
            'unit_name' => ['nullable', 'string', 'max:255'],
            'academic_title' => ['nullable', 'string', 'max:255'],
            'degree' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'research_fields' => ['nullable', 'string', 'max:500'],
            'scientific_achievements' => ['nullable', 'string'],
        ]);
        $this->fillStaffProfileFromUser($data);
        $staff = ! empty($data['user_id'])
            ? ScientificResearchStaffProfile::updateOrCreate(['user_id' => $data['user_id']], $data)
            : ScientificResearchStaffProfile::create($data);
        $this->audit('staff.created', $staff, $staff->full_name, $data);

        return back()->with('success', 'Đã lưu hồ sơ cán bộ NCKH.');
    }

    public function storePlan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'registration_id' => ['required', 'exists:scientific_research_registrations,id'],
            'school_year' => ['nullable', 'string', 'max:30'],
            'plan_year' => ['nullable', 'integer', 'min:2000', 'max:2200'],
            'objectives' => ['nullable', 'string'],
            'assigned_tasks' => ['nullable', 'string'],
            'status' => ['required', 'in:DRAFT,APPROVED,IN_PROGRESS,EVALUATED,CLOSED'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);
        $registration = ScientificResearchRegistration::with('user.unit')->findOrFail($data['registration_id']);
        $this->authorizeRegistrationManager($request, $registration, 'scientific-research.plans.edit');
        $data['assigned_tasks'] = $this->planAssignedTasksPayload($request);
        $plan = ScientificResearchPlan::create($data + [
            'name' => 'Kế hoạch: '.$registration->title,
            'unit_name' => $registration->user?->unit?->name,
            'created_by' => $request->user()->id,
        ]);
        $this->audit('plan.created', $plan, $plan->name, $data);

        return back()->with('success', 'Đã lưu kế hoạch NCKH.');
    }

    public function storeCouncil(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'registration_id' => ['required', 'exists:scientific_research_registrations,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:APPRAISAL,ACCEPTANCE,ADVISORY'],
            'meeting_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'decision_number' => ['nullable', 'string', 'max:255'],
            'conclusion' => ['nullable', 'string'],
            'status' => ['required', 'in:PLANNED,COMPLETED,CANCELLED'],
        ]);
        $council = ScientificResearchCouncil::create($data + ['created_by' => $request->user()->id]);
        $this->audit('council.created', $council, $council->name, $data);

        return back()->with('success', 'Đã lưu hội đồng khoa học.');
    }

    public function storeCouncilMember(Request $request, ScientificResearchCouncil $council): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'full_name' => ['required_without:user_id', 'nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $member = $council->members()->create($data);
        $this->audit('council.member_created', $member, $member->full_name, ['council_id' => $council->id]);

        return back()->with('success', 'Đã thêm thành viên hội đồng.');
    }

    public function storeFunding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'registration_id' => ['required', 'exists:scientific_research_registrations,id'],
            'item_name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:ESTIMATE,ALLOCATED,SPENT,PAYMENT,SETTLEMENT'],
            'amount' => ['required', 'numeric', 'min:0'],
            'spent_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
            'status' => ['required', 'in:PENDING,APPROVED,PAID,SETTLED'],
        ]);
        $duplicate = ScientificResearchFunding::query()
            ->where('registration_id', $data['registration_id'])
            ->where('item_name', $data['item_name'])
            ->where('type', $data['type'])
            ->where('amount', (float) $data['amount'])
            ->when(
                filled($data['spent_on'] ?? null),
                fn ($query) => $query->whereDate('spent_on', $data['spent_on']),
                fn ($query) => $query->whereNull('spent_on')
            )
            ->first();

        if ($duplicate) {
            return back()
                ->withInput()
                ->with('error', 'Khoản kinh phí này đã tồn tại trong đề tài, không tạo thêm dòng trùng. Hãy bấm Sửa nếu cần cập nhật.');
        }

        $funding = ScientificResearchFunding::create($data + ['created_by' => $request->user()->id]);
        $this->audit('funding.created', $funding, $funding->item_name, $data);

        return back()->with('success', 'Đã lưu kinh phí NCKH.');
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'registration_id' => ['required', 'exists:scientific_research_registrations,id'],
            'type' => ['required', 'in:ARTICLE,TEXTBOOK,INITIATIVE,MODEL,WORK,OTHER'],
            'title' => ['required', 'string', 'max:255'],
            'authors' => ['nullable', 'string', 'max:500'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'published_year' => ['nullable', 'integer', 'min:1900', 'max:2200'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:RECORDED,PUBLISHED,ACCEPTED'],
        ]);
        $registration = ScientificResearchRegistration::findOrFail($data['registration_id']);
        $this->authorizeRegistrationParticipantOrManager($request, $registration);
        $product = ScientificResearchProduct::create($data + ['created_by' => $request->user()->id]);
        $this->audit('product.created', $product, $product->title, $data);

        return back()->with('success', 'Đã lưu sản phẩm khoa học.');
    }

    public function storeRepositoryDocument(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'registration_id' => ['required', 'exists:scientific_research_registrations,id'],
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'in:TOPIC_FILE,REPORT,SCIENTIFIC_TEXT,RESULT,OTHER'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'summary' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,zip,rar,txt', 'max:20480'],
        ]);
        $registration = ScientificResearchRegistration::findOrFail($data['registration_id']);
        $this->authorizeRegistrationParticipantOrManager($request, $registration);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $data['file_path'] = $file->store('scientific-research/repository', 'public');
            $data['file_name'] = $file->getClientOriginalName();
        }
        unset($data['file']);
        $document = ScientificResearchRepositoryDocument::create($data + ['created_by' => $request->user()->id]);
        $this->audit('repository.created', $document, $document->title, $data);

        return back()->with('success', 'Đã lưu tài liệu vào kho dữ liệu NCKH.');
    }

    public function updateRegistration(Request $request, ScientificResearchRegistration $registration): RedirectResponse
    {
        $this->authorizeRegistrationOwnerOrEditor($request, $registration);

        $data = $request->validate([
            'research_category_id' => ['required', 'exists:research_categories,id'],
            'announcement_id' => ['nullable', 'exists:scientific_research_announcements,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'topic' => ['nullable', 'string', 'max:255'],
            'academic_year' => ['nullable', 'string', 'max:30'],
            'implementation_year' => ['nullable', 'integer', 'min:2000', 'max:2200'],
            'duration_years' => ['nullable', 'numeric', 'min:0.25', 'max:20'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'product_quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'participant_count' => ['required', 'integer', 'min:1', 'max:999'],
            'lead_contribution_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'member_user_ids' => ['nullable', 'array'],
            'member_user_ids.*' => ['nullable', 'exists:users,id'],
            'member_roles' => ['nullable', 'array'],
            'member_roles.*' => ['nullable', 'string', 'max:100'],
            'member_contribution_percents' => ['nullable', 'array'],
            'member_contribution_percents.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $memberRows = $this->registrationMemberRows($request);
        $leadPercent = (float) ($data['lead_contribution_percent'] ?? 100);
        $this->validateMemberCount((int) $data['participant_count'], $memberRows);
        $this->validateContributionTotal($leadPercent, $memberRows);
        $participantCount = max((int) $data['participant_count'], count($memberRows) + 1);

        DB::transaction(function () use ($request, $registration, $data, $memberRows, $leadPercent, $participantCount): void {
            $registration->update([
                ...collect($data)->except('member_user_ids', 'member_roles', 'member_contribution_percents')->all(),
                'product_quantity' => $data['product_quantity'] ?? 1,
                'duration_years' => $data['duration_years'] ?? 1,
                'participant_count' => $participantCount,
                'lead_contribution_percent' => $leadPercent,
                'updated_by' => $request->user()->id,
            ]);
            $registration->members()->delete();
            $registration->members()->createMany($memberRows);
        });
        $this->audit('registration.updated', $registration, $registration->title, $data);

        return back()->with('success', 'Đã cập nhật đề tài NCKH.');
    }

    public function destroyRegistration(ScientificResearchRegistration $registration): RedirectResponse
    {
        $registration->delete();
        $this->audit('registration.deleted', $registration, $registration->title);

        return redirect()->route('scientific-research.registrations.index')->with('success', 'Đã xóa đề tài NCKH.');
    }

    public function updateResult(Request $request, ScientificResearchResult $result): RedirectResponse
    {
        $result->load('registration.members');
        $this->authorizeRegistrationParticipantOrManager($request, $result->registration);
        $data = $request->validate([
            'completed_on' => ['nullable', 'date'],
            'implementation_year' => ['required', 'integer', 'min:2000', 'max:2200'],
            'summary' => ['nullable', 'string'],
            'status' => ['required', 'in:SUBMITTED,ACCEPTED,NEEDS_REVISION,REJECTED'],
            'review_note' => ['nullable', 'string'],
        ]);

        $result->update($data + [
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'updated_by' => $request->user()->id,
        ]);
        $this->audit('result.updated', $result, $result->registration?->title, $data);

        return back()->with('success', 'Đã cập nhật kết quả NCKH.');
    }

    public function destroyResult(ScientificResearchResult $result): RedirectResponse
    {
        $result->load('registration.members');
        abort_unless(PermissionCheck::userCan('scientific-research.results.delete'), 403);
        $result->delete();
        $this->audit('result.deleted', $result, $result->registration?->title);

        return back()->with('success', 'Đã xóa kết quả NCKH.');
    }

    public function updateStaff(Request $request, ScientificResearchStaffProfile $staffProfile): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'unit_name' => ['nullable', 'string', 'max:255'],
            'academic_title' => ['nullable', 'string', 'max:255'],
            'degree' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'research_fields' => ['nullable', 'string', 'max:500'],
            'scientific_achievements' => ['nullable', 'string'],
        ]);
        $this->fillStaffProfileFromUser($data);
        $staffProfile->update($data);
        $this->audit('staff.updated', $staffProfile, $staffProfile->full_name, $data);

        return back()->with('success', 'Đã cập nhật cán bộ NCKH.');
    }

    public function destroyStaff(ScientificResearchStaffProfile $staffProfile): RedirectResponse
    {
        $staffProfile->delete();
        $this->audit('staff.deleted', $staffProfile, $staffProfile->full_name);

        return back()->with('success', 'Đã xóa cán bộ NCKH.');
    }

    public function updatePlan(Request $request, ScientificResearchPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'registration_id' => ['required', 'exists:scientific_research_registrations,id'],
            'school_year' => ['nullable', 'string', 'max:30'],
            'plan_year' => ['nullable', 'integer', 'min:2000', 'max:2200'],
            'objectives' => ['nullable', 'string'],
            'assigned_tasks' => ['nullable', 'string'],
            'status' => ['required', 'in:DRAFT,APPROVED,IN_PROGRESS,EVALUATED,CLOSED'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);
        $registration = ScientificResearchRegistration::with('user.unit')->findOrFail($data['registration_id']);
        $this->authorizeRegistrationManager($request, $registration, 'scientific-research.plans.edit');
        $data['assigned_tasks'] = $this->planAssignedTasksPayload($request);
        $plan->update($data + [
            'name' => 'Kế hoạch: '.$registration->title,
            'unit_name' => $registration->user?->unit?->name,
        ]);
        $this->audit('plan.updated', $plan, $plan->name, $data);

        return back()->with('success', 'Đã cập nhật kế hoạch NCKH.');
    }

    public function updatePlanTaskProgress(Request $request, ScientificResearchPlan $plan, string $taskKey): RedirectResponse
    {
        $plan->load(['registration.user', 'registration.members']);

        abort_unless($this->canUpdatePlanTask($request, $plan, $taskKey), 403);

        $data = $request->validate([
            'status' => ['required', 'in:ASSIGNED,IN_PROGRESS,COMPLETED,BLOCKED'],
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'completed_on' => ['nullable', 'date'],
            'result_note' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($data['status'] === 'COMPLETED') {
            $data['progress_percent'] = 100;
            $data['completed_on'] = $data['completed_on'] ?? now()->toDateString();
        }

        $payload = json_decode((string) $plan->assigned_tasks, true);
        $rows = collect(($payload['type'] ?? null) === 'by_assignee' ? ($payload['rows'] ?? []) : []);
        $updated = false;

        $rows = $rows->map(function (array $row) use ($taskKey, $data, &$updated): array {
            if ((string) ($row['key'] ?? '') !== (string) $taskKey) {
                return $row;
            }

            $updated = true;

            return array_merge($row, [
                'status' => $data['status'],
                'progress_percent' => (int) $data['progress_percent'],
                'completed_on' => $data['completed_on'] ?? '',
                'result_note' => trim((string) ($data['result_note'] ?? '')),
            ]);
        })->values();

        abort_unless($updated, 404);

        $plan->update([
            'assigned_tasks' => json_encode(['type' => 'by_assignee', 'rows' => $rows->all()], JSON_UNESCAPED_UNICODE),
        ]);
        $this->audit('plan.task_progress_updated', $plan, $plan->name, ['task_key' => $taskKey] + $data);

        $this->notifyPlanOwnerAboutTaskProgress($request, $plan, $taskKey, $data);

        return back()->with('success', 'Đã gửi báo cáo tiến độ cho chủ nhiệm đề tài.');
    }

    public function destroyPlan(ScientificResearchPlan $plan): RedirectResponse
    {
        abort_unless(PermissionCheck::userCan('scientific-research.plans.delete'), 403);
        $plan->delete();
        $this->audit('plan.deleted', $plan, $plan->name);

        return back()->with('success', 'Đã xóa kế hoạch NCKH.');
    }

    public function updateCouncil(Request $request, ScientificResearchCouncil $council): RedirectResponse
    {
        $data = $request->validate([
            'registration_id' => ['nullable', 'exists:scientific_research_registrations,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:APPRAISAL,ACCEPTANCE,ADVISORY'],
            'meeting_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'decision_number' => ['nullable', 'string', 'max:255'],
            'conclusion' => ['nullable', 'string'],
            'status' => ['required', 'in:PLANNED,COMPLETED,CANCELLED'],
        ]);
        $council->update($data);
        $this->audit('council.updated', $council, $council->name, $data);

        return back()->with('success', 'Đã cập nhật hội đồng khoa học.');
    }

    public function destroyCouncil(ScientificResearchCouncil $council): RedirectResponse
    {
        $council->members()->delete();
        $council->delete();
        $this->audit('council.deleted', $council, $council->name);

        return back()->with('success', 'Đã xóa hội đồng khoa học.');
    }

    public function destroyCouncilMember(ScientificResearchCouncilMember $member): RedirectResponse
    {
        $member->delete();
        $this->audit('council.member_deleted', $member, $member->full_name);

        return back()->with('success', 'Đã xóa thành viên hội đồng.');
    }

    public function updateFunding(Request $request, ScientificResearchFunding $funding): RedirectResponse
    {
        $data = $request->validate([
            'registration_id' => ['nullable', 'exists:scientific_research_registrations,id'],
            'item_name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:ESTIMATE,ALLOCATED,SPENT,PAYMENT,SETTLEMENT'],
            'amount' => ['required', 'numeric', 'min:0'],
            'spent_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
            'status' => ['required', 'in:PENDING,APPROVED,PAID,SETTLED'],
        ]);
        $funding->update($data);
        $this->audit('funding.updated', $funding, $funding->item_name, $data);

        return back()->with('success', 'Đã cập nhật kinh phí NCKH.');
    }

    public function destroyFunding(ScientificResearchFunding $funding): RedirectResponse
    {
        $funding->delete();
        $this->audit('funding.deleted', $funding, $funding->item_name);

        return back()->with('success', 'Đã xóa kinh phí NCKH.');
    }

    public function updateProduct(Request $request, ScientificResearchProduct $product): RedirectResponse
    {
        $product->load('registration.members');
        $this->authorizeRegistrationParticipantOrManager($request, $product->registration);
        $data = $request->validate([
            'registration_id' => ['nullable', 'exists:scientific_research_registrations,id'],
            'type' => ['required', 'in:ARTICLE,TEXTBOOK,INITIATIVE,MODEL,WORK,OTHER'],
            'title' => ['required', 'string', 'max:255'],
            'authors' => ['nullable', 'string', 'max:500'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'published_year' => ['nullable', 'integer', 'min:1900', 'max:2200'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:RECORDED,PUBLISHED,ACCEPTED'],
        ]);
        $product->update($data);
        $this->audit('product.updated', $product, $product->title, $data);

        return back()->with('success', 'Đã cập nhật sản phẩm khoa học.');
    }

    public function destroyProduct(ScientificResearchProduct $product): RedirectResponse
    {
        $product->load('registration.members');
        abort_unless(PermissionCheck::userCan('scientific-research.products.delete')
            || ($this->isRegistrationParticipant(request(), $product->registration) && PermissionCheck::userCan('scientific-research.products.edit')), 403);
        $product->delete();
        $this->audit('product.deleted', $product, $product->title);

        return back()->with('success', 'Đã xóa sản phẩm khoa học.');
    }

    public function updateRepositoryDocument(Request $request, ScientificResearchRepositoryDocument $document): RedirectResponse
    {
        $document->load('registration.members');
        $this->authorizeRegistrationParticipantOrManager($request, $document->registration);
        $data = $request->validate([
            'registration_id' => ['nullable', 'exists:scientific_research_registrations,id'],
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'in:TOPIC_FILE,REPORT,SCIENTIFIC_TEXT,RESULT,OTHER'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'summary' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,zip,rar,txt', 'max:20480'],
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $data['file_path'] = $file->store('scientific-research/repository', 'public');
            $data['file_name'] = $file->getClientOriginalName();
        }
        unset($data['file']);
        $document->update($data);
        $this->audit('repository.updated', $document, $document->title, $data);

        return back()->with('success', 'Đã cập nhật tài liệu NCKH.');
    }

    public function destroyRepositoryDocument(ScientificResearchRepositoryDocument $document): RedirectResponse
    {
        $document->load('registration.members');
        abort_unless(PermissionCheck::userCan('scientific-research.repository.delete')
            || ($this->isRegistrationParticipant(request(), $document->registration) && PermissionCheck::userCan('scientific-research.repository.edit')), 403);
        $document->delete();
        $this->audit('repository.deleted', $document, $document->title);

        return back()->with('success', 'Đã xóa tài liệu NCKH.');
    }

    private function fillStaffProfileFromUser(array &$data): void
    {
        if (empty($data['user_id'])) {
            return;
        }

        $user = User::query()
            ->with('unit:id,name')
            ->find($data['user_id']);

        if (! $user) {
            return;
        }

        $data['full_name'] = $user->name;
        $data['unit_name'] = $user->unit?->name;
    }

    private function internalUsers()
    {
        return User::query()
            ->with('unit:id,name')
            ->where('status', 1)
            ->where('user_type', 'instructor')
            ->whereNotNull('instructor_id')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'unit_id', 'instructor_id', 'user_type']);
    }

    private function planAssignedTasksPayload(Request $request): ?string
    {
        $rows = [];

        foreach ((array) $request->input('assignee_tasks', []) as $key => $task) {
            if (! is_array($task)) {
                continue;
            }

            $content = trim((string) ($task['task'] ?? ''));
            if ($content === '') {
                continue;
            }

            $status = in_array(($task['status'] ?? 'ASSIGNED'), ['ASSIGNED', 'IN_PROGRESS', 'COMPLETED', 'BLOCKED'], true)
                ? $task['status']
                : 'ASSIGNED';
            $progress = min(100, max(0, (int) ($task['progress_percent'] ?? 0)));
            $completedOn = trim((string) ($task['completed_on'] ?? ''));

            if ($status === 'COMPLETED') {
                $progress = 100;
                $completedOn = $completedOn ?: now()->toDateString();
            }

            $rows[] = [
                'key' => (string) $key,
                'name' => trim((string) ($task['name'] ?? '')),
                'role' => trim((string) ($task['role'] ?? '')),
                'unit' => trim((string) ($task['unit'] ?? '')),
                'task' => $content,
                'status' => $status,
                'progress_percent' => $progress,
                'completed_on' => $completedOn,
                'result_note' => trim((string) ($task['result_note'] ?? '')),
            ];
        }

        if ($rows !== []) {
            return json_encode(['type' => 'by_assignee', 'rows' => $rows], JSON_UNESCAPED_UNICODE);
        }

        $fallback = trim((string) $request->input('assigned_tasks', ''));

        return $fallback !== '' ? $fallback : null;
    }

    private function notifyPlanOwnerAboutTaskProgress(Request $request, ScientificResearchPlan $plan, string $taskKey, array $data): void
    {
        $owner = $plan->registration?->user;
        $actor = $request->user();

        if (! $owner || ! $actor || (int) $owner->id === (int) $actor->id) {
            return;
        }

        $payload = json_decode((string) $plan->assigned_tasks, true);
        $task = collect(($payload['type'] ?? null) === 'by_assignee' ? ($payload['rows'] ?? []) : [])
            ->first(fn (array $row) => (string) ($row['key'] ?? '') === (string) $taskKey);

        $taskName = trim((string) ($task['task'] ?? 'nhiệm vụ được giao'));
        $note = trim((string) ($data['result_note'] ?? ''));
        $message = $actor->name.' đã gửi báo cáo tiến độ '.$data['progress_percent'].'% cho nhiệm vụ: '.$taskName;
        if ($note !== '') {
            $message .= "\nGhi chú: ".$note;
        }

        SystemNotifier::notifyUser(
            recipient: $owner,
            actor: $actor,
            module: 'scientific-research',
            action: 'plan.task_progress_updated',
            title: 'Thành viên báo cáo tiến độ NCKH',
            message: $message,
            url: route('scientific-research.plans.index'),
            type: SystemNotifier::TYPE_SYSTEM_CHANGE,
            meta: [
                'plan_id' => $plan->id,
                'registration_id' => $plan->registration_id,
                'task_key' => $taskKey,
            ],
            sendEmail: false,
        );
    }

    private function canUpdatePlanTask(Request $request, ScientificResearchPlan $plan, string $taskKey): bool
    {
        if (PermissionCheck::can($request->user(), 'scientific-research.plans.edit')) {
            return true;
        }

        $userId = (int) $request->user()->id;

        if (str_starts_with($taskKey, 'lead-')) {
            return (int) str_replace('lead-', '', $taskKey) === $userId
                && (int) $plan->registration?->user_id === $userId;
        }

        if (str_starts_with($taskKey, 'member-')) {
            $memberId = (int) str_replace('member-', '', $taskKey);

            return $plan->registration?->members
                ->contains(fn ($member) => (int) $member->id === $memberId && (int) $member->user_id === $userId) ?? false;
        }

        return false;
    }

    private function registrationMemberRows(Request $request): array
    {
        $userIds = (array) $request->input('member_user_ids', []);
        $roles = (array) $request->input('member_roles', []);
        $percents = (array) $request->input('member_contribution_percents', []);
        $ids = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0 && $id !== (int) $request->user()->id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $users = User::query()
            ->with('unit:id,name')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $rows = [];
        foreach ($ids as $sortIndex => $userId) {
            $user = $users->get($userId);
            if (! $user) {
                continue;
            }

            $originalIndex = array_search((string) $userId, array_map('strval', $userIds), true);
            $rows[] = [
                'user_id' => $user->id,
                'full_name' => $user->name,
                'unit_name' => $user->unit?->name,
                'role' => trim((string) ($roles[$originalIndex] ?? '')) ?: 'Thành viên',
                'contribution_percent' => round((float) ($percents[$originalIndex] ?? 0), 2),
                'sort_order' => $sortIndex + 1,
            ];
        }

        return $rows;
    }

    private function validateContributionTotal(float $leadPercent, array $memberRows): void
    {
        $total = $leadPercent + array_sum(array_map(fn (array $row) => (float) $row['contribution_percent'], $memberRows));

        if ($total > 100.01) {
            throw ValidationException::withMessages([
                'lead_contribution_percent' => 'Tổng tỷ lệ đóng góp của chủ nhiệm và thành viên không được vượt quá 100%.',
            ]);
        }
    }

    private function validateMemberCount(int $participantCount, array $memberRows): void
    {
        $expectedMembers = max(0, $participantCount - 1);

        if (count($memberRows) < $expectedMembers) {
            throw ValidationException::withMessages([
                'participant_count' => 'Số lượng đồng tác giả đã chọn chưa đủ so với tổng số người tham gia.',
            ]);
        }
    }

    private function authorizeRegistrationStatusChange(Request $request, string $nextStatus): void
    {
        $permission = match ($nextStatus) {
            ScientificResearchRegistration::STATUS_UNIT_APPROVED => 'scientific-research.registrations.unit-approve',
            ScientificResearchRegistration::STATUS_NEEDS_REVISION => 'scientific-research.registrations.return',
            ScientificResearchRegistration::STATUS_UNDER_REVIEW,
            ScientificResearchRegistration::STATUS_APPROVED,
            ScientificResearchRegistration::STATUS_REJECTED => 'scientific-research.registrations.agency-approve',
            default => 'scientific-research.registrations.status',
        };

        abort_unless(PermissionCheck::can($request->user(), $permission), 403);
    }

    private function canManageRegistrations(Request $request): bool
    {
        return collect([
            'scientific-research.registrations.edit',
            'scientific-research.registrations.delete',
            'scientific-research.registrations.status',
            'scientific-research.registrations.unit-approve',
            'scientific-research.registrations.agency-approve',
            'scientific-research.registrations.return',
        ])->contains(fn (string $permission) => PermissionCheck::can($request->user(), $permission));
    }

    private function canManageResults(Request $request): bool
    {
        return collect([
            'scientific-research.results.approve',
            'scientific-research.results.delete',
            'scientific-research.registrations.edit',
        ])->contains(fn (string $permission) => PermissionCheck::can($request->user(), $permission));
    }

    private function canManageScientificRecords(Request $request, string $module): bool
    {
        return PermissionCheck::can($request->user(), $module.'.delete')
            || PermissionCheck::can($request->user(), 'scientific-research.registrations.edit');
    }

    private function scopeRegistrationParticipation(Builder $query, Request $request): Builder
    {
        $userId = (int) $request->user()->id;

        return $query->where(function (Builder $participantQuery) use ($userId): void {
            $participantQuery
                ->where('user_id', $userId)
                ->orWhereHas('members', fn (Builder $memberQuery) => $memberQuery->where('user_id', $userId));
        });
    }

    private function isRegistrationParticipant(Request $request, ?ScientificResearchRegistration $registration): bool
    {
        if (! $registration) {
            return false;
        }

        $userId = (int) $request->user()->id;

        if ((int) $registration->user_id === $userId) {
            return true;
        }

        if (! $registration->relationLoaded('members')) {
            $registration->load('members');
        }

        return $registration->members->contains(fn ($member) => (int) $member->user_id === $userId);
    }

    private function authorizeRegistrationVisible(Request $request, ScientificResearchRegistration $registration): void
    {
        abort_unless($this->canManageRegistrations($request) || $this->isRegistrationParticipant($request, $registration), 403);
    }

    private function authorizeRegistrationManager(Request $request, ScientificResearchRegistration $registration, string $permission): void
    {
        abort_unless(PermissionCheck::can($request->user(), $permission) && $this->canManageRegistrations($request), 403);
    }

    private function authorizeRegistrationParticipantOrManager(Request $request, ?ScientificResearchRegistration $registration): void
    {
        abort_unless($this->canManageRegistrations($request) || $this->isRegistrationParticipant($request, $registration), 403);
    }

    private function authorizeRegistrationOwnerOrEditor(Request $request, ScientificResearchRegistration $registration): void
    {
        abort_unless(
            (int) $registration->user_id === (int) $request->user()->id
            || PermissionCheck::can($request->user(), 'scientific-research.registrations.edit'),
            403
        );
    }

    private function activeResearchCategories()
    {
        return ResearchCategory::query()
            ->active()
            ->orderBy('code')
            ->orderBy('name')
            ->get();
    }

    private function openAnnouncements()
    {
        return ScientificResearchAnnouncement::query()
            ->where('status', ScientificResearchAnnouncement::STATUS_OPEN)
            ->where(fn ($query) => $query->whereNull('closes_at')->orWhere('closes_at', '>=', now()))
            ->latest()
            ->get();
    }

    private function registrationStatuses(): array
    {
        return [
            ScientificResearchRegistration::STATUS_DRAFT => 'Bản nháp',
            ScientificResearchRegistration::STATUS_PROPOSED => 'Đề xuất nhiệm vụ',
            ScientificResearchRegistration::STATUS_SUBMITTED => 'Chờ chỉ huy đơn vị duyệt',
            ScientificResearchRegistration::STATUS_UNIT_APPROVED => 'Chỉ huy đơn vị đã duyệt',
            ScientificResearchRegistration::STATUS_UNDER_REVIEW => 'Đang thẩm định',
            ScientificResearchRegistration::STATUS_NEEDS_REVISION => 'Cần bổ sung',
            ScientificResearchRegistration::STATUS_APPROVED => 'Đã duyệt',
            ScientificResearchRegistration::STATUS_IN_PROGRESS => 'Đang thực hiện',
            ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED => 'Xin gia hạn',
            ScientificResearchRegistration::STATUS_ACCEPTANCE_PENDING => 'Chờ nghiệm thu',
            ScientificResearchRegistration::STATUS_COMPLETED => 'Hoàn thành',
            ScientificResearchRegistration::STATUS_REJECTED => 'Từ chối',
        ];
    }

    private function allowedStatusTransitions(): array
    {
        return [
            ScientificResearchRegistration::STATUS_DRAFT => [
                ScientificResearchRegistration::STATUS_PROPOSED,
                ScientificResearchRegistration::STATUS_SUBMITTED,
                ScientificResearchRegistration::STATUS_REJECTED,
            ],
            ScientificResearchRegistration::STATUS_PROPOSED => [
                ScientificResearchRegistration::STATUS_UNIT_APPROVED,
                ScientificResearchRegistration::STATUS_NEEDS_REVISION,
                ScientificResearchRegistration::STATUS_REJECTED,
            ],
            ScientificResearchRegistration::STATUS_SUBMITTED => [
                ScientificResearchRegistration::STATUS_UNIT_APPROVED,
                ScientificResearchRegistration::STATUS_NEEDS_REVISION,
                ScientificResearchRegistration::STATUS_REJECTED,
            ],
            ScientificResearchRegistration::STATUS_UNIT_APPROVED => [
                ScientificResearchRegistration::STATUS_UNDER_REVIEW,
                ScientificResearchRegistration::STATUS_NEEDS_REVISION,
                ScientificResearchRegistration::STATUS_REJECTED,
            ],
            ScientificResearchRegistration::STATUS_UNDER_REVIEW => [
                ScientificResearchRegistration::STATUS_NEEDS_REVISION,
                ScientificResearchRegistration::STATUS_APPROVED,
                ScientificResearchRegistration::STATUS_REJECTED,
            ],
            ScientificResearchRegistration::STATUS_NEEDS_REVISION => [
                ScientificResearchRegistration::STATUS_SUBMITTED,
                ScientificResearchRegistration::STATUS_UNDER_REVIEW,
                ScientificResearchRegistration::STATUS_REJECTED,
            ],
            ScientificResearchRegistration::STATUS_APPROVED => [
                ScientificResearchRegistration::STATUS_IN_PROGRESS,
                ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED,
                ScientificResearchRegistration::STATUS_ACCEPTANCE_PENDING,
            ],
            ScientificResearchRegistration::STATUS_IN_PROGRESS => [
                ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED,
                ScientificResearchRegistration::STATUS_ACCEPTANCE_PENDING,
            ],
            ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED => [
                ScientificResearchRegistration::STATUS_IN_PROGRESS,
                ScientificResearchRegistration::STATUS_ACCEPTANCE_PENDING,
                ScientificResearchRegistration::STATUS_REJECTED,
            ],
            ScientificResearchRegistration::STATUS_ACCEPTANCE_PENDING => [
                ScientificResearchRegistration::STATUS_COMPLETED,
                ScientificResearchRegistration::STATUS_NEEDS_REVISION,
            ],
            ScientificResearchRegistration::STATUS_COMPLETED => [],
            ScientificResearchRegistration::STATUS_REJECTED => [],
        ];
    }

    private function reportRows()
    {
        return ScientificResearchRegistration::with(['user', 'researchCategory'])
            ->withCount('results')
            ->orderBy('project_code')
            ->get();
    }

    private function audit(string $action, $target = null, ?string $title = null, array $changes = []): void
    {
        ScientificResearchAuditLog::create([
            'action' => $action,
            'target_type' => is_object($target) ? $target::class : null,
            'target_id' => is_object($target) && method_exists($target, 'getKey') ? $target->getKey() : null,
            'title' => $title,
            'changes' => $changes ?: null,
            'created_by' => auth()->id(),
        ]);
    }

    private function storeFiles(Request $request, string $input, string $ownerType, int $ownerId): void
    {
        foreach ($request->file($input, []) as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('scientific-research/'.$ownerType, 'public');
            ScientificResearchFile::create([
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize() ?: 0,
                'uploaded_by' => $request->user()?->id,
            ]);
        }
    }
}
