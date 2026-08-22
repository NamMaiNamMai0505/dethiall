<?php

namespace Modules\Grades\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Grades\Services\GradeAccess;
use Modules\Grades\Services\GradeActor;

/**
 * Menu chính Tổng kết / TN / trích ngang / vào điểm.
 */
class AcademicHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function __invoke()
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);

        $role = GradeActor::resolve($user);
        $roleLabel = GradeActor::label($user);

        $cards = [
            [
                'key' => 'entry',
                'title' => 'Chọn lớp · môn vào điểm',
                'desc' => 'Wizard nhập điểm theo quyền (Môn→Lớp hoặc Khoa→Môn→Lớp).',
                'icon' => 'bi-pencil-square',
                'route' => 'grades.hub',
                'show' => true,
            ],
            [
                'key' => 'summary',
                'title' => 'Tổng kết khóa học · Xét tốt nghiệp',
                'desc' => 'Năm học, lọc danh sách, tính kết quả, phê duyệt, in sổ điểm.',
                'icon' => 'bi-mortarboard',
                'route' => 'grades.academic.summary.index',
                'show' => true,
            ],
            [
                'key' => 'profile',
                'title' => 'Đăng ký hồ sơ tốt nghiệp',
                'desc' => 'Ngành, khóa, hệ, quyết định, series văn bằng, in phiếu điểm.',
                'icon' => 'bi-file-earmark-person',
                'route' => 'grades.academic.profiles.index',
                'show' => true,
            ],
            [
                'key' => 'extract',
                'title' => 'Danh sách trích ngang',
                'desc' => 'Xem / sửa (theo quyền) thông tin học viên và in danh sách.',
                'icon' => 'bi-people',
                'route' => 'grades.academic.extracts.index',
                'show' => true,
            ],
            [
                'key' => 'score_entry',
                'title' => 'Vào điểm lớp · môn',
                'desc' => 'Chọn lớp–năm–học phần; vào điểm trực tiếp / file; rèn luyện.',
                'icon' => 'bi-table',
                'route' => 'grades.academic.entry.index',
                'show' => true,
            ],
            [
                'key' => 'settings',
                'title' => 'Cài đặt Quản lý điểm',
                'desc' => 'Năm học dùng chung, thang điểm, điểm đạt và quy tắc làm tròn.',
                'icon' => 'bi-gear',
                'route' => 'settings.grades',
                'show' => true,
            ],
        ];

        $caps = [
            'edit' => GradeActor::canEditOperational($user),
            'approve' => GradeActor::canApprove($user),
            'print' => GradeActor::canPrint($user),
            'print_diploma' => GradeActor::canPrintDiploma($user),
            'enter_scores' => GradeActor::canEnterScores($user),
        ];

        return view('grades::academic.hub', compact('cards', 'role', 'roleLabel', 'caps'));
    }
}
