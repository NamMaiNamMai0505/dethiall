<?php

namespace Modules\Trash\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TrashService;
use App\Support\TrashRegistry;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function __construct(
        private readonly TrashService $trashService
    ) {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || ! $user->isManagementActor()) {
                abort(403, 'Chỉ Super Admin hoặc Quản lý mới được truy cập Thùng rác.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $filters = $request->only(['module', 'search', 'page']);
        $perPage = (int) $request->get('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        try {
            $items = $this->trashService->paginate($filters, $perPage);
            $moduleOptions = TrashRegistry::options();
            $counts = $this->trashService->countsByModule();
            $totalCount = array_sum($counts);
        } catch (\Throwable $e) {
            report($e);
            $items = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
            $moduleOptions = TrashRegistry::options();
            $counts = [];
            $totalCount = 0;

            return view('trash::index', compact('items', 'moduleOptions', 'counts', 'totalCount', 'filters'))
                ->with('error', 'Không tải được thùng rác. Kiểm tra migration bảng trash_logs / soft deletes: '.$e->getMessage());
        }

        return view('trash::index', compact('items', 'moduleOptions', 'counts', 'totalCount', 'filters'));
    }

    public function show(string $module, int $id)
    {
        if (! TrashRegistry::find($module)) {
            abort(404);
        }

        $item = $this->trashService->findItem($module, $id);
        if (! $item) {
            abort(404, 'Không tìm thấy mục trong thùng rác.');
        }

        return view('trash::show', compact('item'));
    }

    public function restore(string $module, int $id)
    {
        if (! TrashRegistry::find($module)) {
            abort(404);
        }

        try {
            $model = $this->trashService->restore($module, $id);
            $label = TrashRegistry::find($module)['label'] ?? $module;

            return redirect()
                ->route('trash.index')
                ->with('success', "Đã khôi phục {$label}: ".($model->name ?? $model->code ?? '#'.$id));
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Không khôi phục được: '.$e->getMessage());
        }
    }

    public function forceDelete(Request $request, string $module, int $id)
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Chỉ Super Admin mới được xóa vĩnh viễn.');
        }

        if (! TrashRegistry::find($module)) {
            abort(404);
        }

        try {
            $this->trashService->forceDelete($module, $id);

            return redirect()
                ->route('trash.index')
                ->with('success', 'Đã xóa vĩnh viễn mục khỏi thùng rác (không thể khôi phục).');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Không xóa được: '.$e->getMessage());
        }
    }

    /**
     * Khôi phục nhiều mục cùng lúc (chọn nhiều qua checkbox). Mỗi mục ghi
     * dạng "module:id" vì thùng rác gộp nhiều loại dữ liệu khác nhau.
     */
    public function bulkRestore(Request $request)
    {
        $items = $this->parseBulkItems($request);
        if (empty($items)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một mục để khôi phục.');
        }

        $restored = 0;
        $failed = 0;
        foreach ($items as [$module, $id]) {
            if (! TrashRegistry::find($module)) {
                $failed++;

                continue;
            }
            try {
                $this->trashService->restore($module, $id);
                $restored++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        $message = "Đã khôi phục {$restored} mục.";
        if ($failed > 0) {
            $message .= " {$failed} mục lỗi/không tìm thấy, đã bỏ qua.";
        }

        return redirect()->route('trash.index')->with($failed > 0 ? 'error' : 'success', $message);
    }

    /**
     * Xóa vĩnh viễn nhiều mục cùng lúc - chỉ Super Admin, không thể hoàn tác.
     */
    public function bulkForceDelete(Request $request)
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Chỉ Super Admin mới được xóa vĩnh viễn.');
        }

        $items = $this->parseBulkItems($request);
        if (empty($items)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một mục để xóa vĩnh viễn.');
        }

        $deleted = 0;
        $failed = 0;
        foreach ($items as [$module, $id]) {
            if (! TrashRegistry::find($module)) {
                $failed++;

                continue;
            }
            try {
                $this->trashService->forceDelete($module, $id);
                $deleted++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        $message = "Đã xóa vĩnh viễn {$deleted} mục (không thể khôi phục).";
        if ($failed > 0) {
            $message .= " {$failed} mục lỗi/không tìm thấy, đã bỏ qua.";
        }

        return redirect()->route('trash.index')->with($failed > 0 ? 'error' : 'success', $message);
    }

    /**
     * @return list<array{0: string, 1: int}>
     */
    private function parseBulkItems(Request $request): array
    {
        $raw = (array) $request->input('items', []);
        $items = [];
        foreach ($raw as $entry) {
            if (! is_string($entry) || ! str_contains($entry, ':')) {
                continue;
            }
            [$module, $id] = explode(':', $entry, 2);
            if (! ctype_digit($id)) {
                continue;
            }
            $items[] = [$module, (int) $id];
        }

        return $items;
    }
}
