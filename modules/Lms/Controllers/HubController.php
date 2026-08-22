<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsLesson;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;

class HubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:lms.index']);
    }

    public function index(LmsCourseService $service)
    {
        // Học viên / GV: portal riêng — không vào admin hub
        if (LmsAccess::usesLearnerShell()) {
            return redirect()->route('lms.learn.home');
        }

        $user = Auth::user();
        $stats = [
            'courses' => (clone $service->queryForUser($user))->count(),
            'published' => (clone $service->queryForUser($user))->where('status', LmsCourse::STATUS_PUBLISHED)->count(),
            'lessons' => LmsLesson::query()
                ->whereIn('lms_course_id', (clone $service->queryForUser($user))->select('id'))
                ->count(),
        ];

        $menu = [
            [
                'route' => 'lms.courses.index',
                'label' => 'Khóa học LMS',
                'desc' => 'Khóa học gắn môn, lớp; quản lý tài liệu, SCORM, diễn đàn và chat.',
                'icon' => 'bi-collection-play',
                'iconBg' => 'bg-blue-100 text-blue-700',
                'perm' => 'lms.index',
                'primary' => true,
            ],
            [
                'route' => 'lms.provisioning.index',
                'label' => 'Đồng bộ lịch đào tạo',
                'desc' => 'Bổ sung GV thực dạy, bài học và buổi điểm danh vào lớp học phần đã có.',
                'icon' => 'bi-diagram-3',
                'iconBg' => 'bg-cyan-100 text-cyan-800',
                'perm' => 'lms.create',
            ],
            [
                'route' => 'teaching-assignments.index',
                'label' => 'Phân công giảng viên',
                'desc' => 'Khoa chọn giảng viên cho môn; LMS tự ghi nhận vào mọi lớp đúng ngành.',
                'icon' => 'bi-person-check',
                'iconBg' => 'bg-emerald-100 text-emerald-800',
                'perm' => 'teaching-assignments.index',
            ],
            [
                'route' => 'lms.courses.create',
                'label' => 'Lớp học phần độc lập',
                'desc' => 'Chỉ dùng cho lớp đặc biệt không thuộc ngành/lớp hành chính.',
                'icon' => 'bi-plus-circle',
                'iconBg' => 'bg-indigo-100 text-indigo-700',
                'perm' => 'lms.create',
            ],
            [
                'route' => 'lms.survey-templates.index',
                'label' => 'Template khảo sát',
                'desc' => 'Tái sử dụng bộ câu hỏi khảo sát giữa nhiều khóa học.',
                'icon' => 'bi-clipboard-data',
                'iconBg' => 'bg-violet-100 text-violet-800',
                'perm' => 'lms.create',
            ],
            [
                'route' => 'lms.gradebook.export-multi',
                'label' => 'Export điểm multi-khóa',
                'desc' => 'Tổng hợp và tải bảng điểm của nhiều khóa học.',
                'icon' => 'bi-download',
                'iconBg' => 'bg-emerald-100 text-emerald-800',
                'perm' => 'lms.create',
            ],
            [
                'route' => 'lms.learn.home',
                'label' => 'Xem cổng học tập',
                'desc' => 'Giao diện học viên (preview) — tách biệt dashboard quản trị',
                'icon' => 'bi-phone',
                'iconBg' => 'bg-teal-100 text-teal-800',
                'perm' => 'lms.index',
            ],
            [
                'route' => 'settings.lms',
                'label' => 'Cài đặt LMS',
                'desc' => 'Năm học dùng chung và trạng thái mặc định khi tạo khóa học.',
                'icon' => 'bi-gear',
                'iconBg' => 'bg-slate-100 text-slate-800',
                'perm' => 'lms.index',
            ],
        ];

        $menu = collect($menu)->filter(fn ($i) => $user->can($i['perm']))->values()->all();

        return view('lms::hub', compact('stats', 'menu', 'user'));
    }
}
