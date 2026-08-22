<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;
use Illuminate\View\View;

class Breadcrumb extends Component
{
    /**
     * @var list<array{title: string, url: string|null}>
     */
    public array $items;

    /**
     * @param  list<array{title?: string, url?: string|null}>  $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $this->normalize($items);
    }

    public function render(): View
    {
        return view('components.breadcrumb');
    }

    /**
     * @param  list<array{title?: string, url?: string|null}>  $items
     * @return list<array{title: string, url: string|null}>
     */
    private function normalize(array $items): array
    {
        $count = count($items);
        $resolved = [];

        foreach ($items as $index => $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $url = $item['url'] ?? null;

            if (is_string($url)) {
                $url = trim($url) !== '' ? $url : null;
            } else {
                $url = null;
            }

            // Tự gán URL theo title nếu thiếu
            if ($url === null) {
                $url = $this->resolveUrlByTitle($title);
            }

            // Phần tử cuối vẫn phải bấm được → link trang hiện tại
            if ($url === null && $index === $count - 1) {
                $url = url()->current();
            }

            $resolved[] = [
                'title' => $title,
                'url' => $url,
            ];
        }

        return $resolved;
    }

    private function resolveUrlByTitle(string $title): ?string
    {
        $key = mb_strtolower(trim($title));

        // Chuẩn hóa nhẹ
        $key = preg_replace('/\s+/u', ' ', $key) ?? $key;

        $map = $this->titleRouteMap();

        if (! isset($map[$key])) {
            return null;
        }

        $routeName = $map[$key];

        if (! is_string($routeName) || $routeName === '' || ! Route::has($routeName)) {
            return null;
        }

        try {
            return route($routeName);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Map title breadcrumb (tiếng Việt) → tên route.
     *
     * @return array<string, string>
     */
    private function titleRouteMap(): array
    {
        return [
            // Chung
            'trang chủ' => 'dashboard',
            'home' => 'dashboard',
            'dashboard' => 'dashboard',
            'dashboard tổng quan' => 'dashboard',

            // Core modules
            'quản lý tài khoản' => 'accounts.hub',
            'người dùng' => 'users.index',
            'người dùng nội bộ' => 'users.index',
            'vai trò' => 'roles.index',
            'vai trò & phân quyền' => 'roles.index',
            'quyền' => 'permissions.index',
            'thông tin tài khoản' => 'profile',
            'hồ sơ' => 'profile',

            'học viên' => 'students.index',
            'giảng viên' => 'instructors.index',
            'lớp học' => 'classes.index',
            'môn học' => 'subjects.index',
            'ngành đào tạo' => 'specializations.index',
            'đơn vị' => 'units.index',
            'phòng học' => 'classrooms.index',
            'giảng đường' => 'buildings.index',

            'lịch đào tạo' => 'training-schedules.index',
            'phân công giảng viên' => 'teaching-assignments.index',
            'phân công giảng dạy' => 'teaching-assignments.index',
            'lịch giảng dạy của tôi' => 'instructor-schedule.index',
            'lịch học của tôi' => 'student-schedule.index',
            'thời khóa biểu tổng hợp' => 'training-schedules.calendar',
            'lịch học' => 'training-schedules.calendar',

            // Standard hours
            'giờ chuẩn gv' => 'standard-hours.hub',
            'giờ chuẩn giảng viên' => 'standard-hours.hub',
            'đối tượng' => 'standard-hours.object-types.index',
            'chức danh' => 'standard-hours.positions.index',
            'định mức giờ' => 'standard-hours.object-types.index',
            'định mức nckh' => 'standard-hours.object-types.index',
            'hđ chuyên môn' => 'standard-hours.conversion-categories.index',
            'hoạt động chuyên môn' => 'standard-hours.conversion-categories.index',
            'danh mục nckh' => 'standard-hours.research-categories.index',
            'kê khai hđ cm' => 'standard-hours.conversion-records.index',
            'kê khai nckh' => 'standard-hours.research-records.index',
            'tính giờ chuẩn' => 'standard-hours.calculations.index',
            'kết quả của tôi' => 'standard-hours.my-results.index',
            'báo cáo' => 'standard-hours.reports.index',
            'quy đổi giờ' => 'standard-hours.hour-exchanges.index',
            'luật quy đổi nckh' => 'standard-hours.settings.research-rules',
        ];
    }
}
