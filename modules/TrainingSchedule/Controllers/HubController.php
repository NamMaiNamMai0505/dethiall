<?php

namespace Modules\TrainingSchedule\Controllers;

use App\Http\Controllers\Controller;
use App\Support\TrainingDept;
use App\Support\ManagementRole;
use App\Support\TrainingScheduleAccess;
use Illuminate\Support\Facades\Auth;
use Modules\TrainingSchedule\Models\TrainingSchedule;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Role;

class HubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:training-schedules.index']);
    }

    public function index()
    {
        TrainingScheduleAccess::ensureValidRoleConfiguration();
        $user = Auth::user();
        $user?->loadMissing('unit');

        $facultyRoleId = Role::query()->where('name', ManagementRole::FACULTY_SCHEDULE_MANAGER)->value('id');
        $unit = $user?->unit_id ? Unit::query()->find($user->unit_id) : null;
        abort_if(
            ($user?->hasRole(ManagementRole::FACULTY_SCHEDULE_MANAGER)
                || ($facultyRoleId && (int) $user?->role_id === (int) $facultyRoleId))
                && (! $unit
                    || $unit->functional_type !== Unit::FUNCTIONAL_FACULTY
                    || ! $unit->faculty_code),
            403,
            'Vai trò quản lý lịch chưa được gắn đúng loại đơn vị. Vui lòng liên hệ quản trị hệ thống.'
        );

        $stats = [
            'total' => TrainingSchedule::query()->count(),
            'active' => TrainingSchedule::query()->where('is_active', true)->count(),
            'inactive' => TrainingSchedule::query()->where('is_active', false)->count(),
        ];

        $isTrainingOffice = TrainingDept::isTrainingOffice($user);
        $isFaculty = TrainingDept::isFacultyManager($user);
        $isSuper = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
        $unitIsPdot = $isTrainingOffice;

        // Một khung form chung: PDOT xếp môn/loại/phòng — Khoa gán bài/GV (theo quyền tài khoản)
        $allocateDesc = match (true) {
            $isSuper => 'Chọn lịch → ngày: full (khung + bài + GV)',
            $unitIsPdot => 'Chọn lịch → ngày: xếp môn, loại tiết, địa điểm (khung). Bài & GV do Khoa.',
            $isFaculty => 'Chọn lịch → ngày: gán bài học & giảng viên trên khung PDOT đã xếp (môn khoa mình).',
            default => 'Chọn lịch → ngày: phân chia tiết theo quyền tài khoản.',
        };

        /*
         * scope:
         * - training_office: chỉ PDOT / super-admin
         * - faculty: Khoa / super-admin
         * - both: mọi ai có quyền Spatie
         */
        $menuItems = [
            [
                'route' => 'training-schedules.list',
                'icon' => 'bi-calendar2-week',
                'label' => 'Phân chia lịch học',
                'desc' => $allocateDesc,
                'perm' => 'training-schedules.index',
                'iconBg' => 'bg-blue-100 text-blue-700',
                'scope' => 'both',
                'primary' => true,
            ],
            [
                'route' => 'training-schedules.list',
                'icon' => 'bi-list-ul',
                'label' => 'Danh sách lịch đào tạo',
                'desc' => 'Xem / lọc khung lịch theo lớp, ngành, học kỳ',
                'perm' => 'training-schedules.index',
                'iconBg' => 'bg-slate-100 text-slate-700',
                'scope' => 'both',
                // Trùng route với mục chính — ẩn để tránh 2 card giống nhau
                'hidden' => true,
            ],
            [
                'route' => 'training-schedules.create',
                'icon' => 'bi-plus-circle',
                'label' => 'Tạo lịch đào tạo mới',
                'desc' => 'Tạo khung lịch (lớp, khoảng ngày, học kỳ) — sau đó phân chia tiết trong danh sách',
                'perm' => 'training-schedules.create',
                'iconBg' => 'bg-indigo-100 text-indigo-700',
                'scope' => 'training_office',
            ],
            [
                'route' => 'training-schedules.calendar',
                'icon' => 'bi-grid-3x3-gap',
                'label' => 'Lịch tổng hợp & xuất',
                'desc' => 'Xem TKB theo lớp/ngành; xuất Word (lịch học / LHL / kế hoạch Khoa)',
                'perm' => 'training-schedules.index',
                'iconBg' => 'bg-emerald-100 text-emerald-700',
                'scope' => 'both',
            ],
            [
                'route' => 'teaching-assignments.index',
                'icon' => 'bi-person-video3',
                'label' => 'Phân công giảng dạy',
                'desc' => 'Gán môn cho GV — nguồn danh sách GV khi phân tiết (Khoa)',
                'perm' => 'teaching-assignments.index',
                'iconBg' => 'bg-amber-100 text-amber-800',
                'scope' => 'both',
            ],
            [
                'route' => 'subjects.index',
                'icon' => 'bi-journal-text',
                'label' => 'Môn học',
                'desc' => 'Danh mục môn, viết tắt, màu nhận diện',
                'perm' => 'subjects.index',
                'iconBg' => 'bg-purple-100 text-purple-700',
                'scope' => 'both',
            ],
            [
                'route' => 'subject-lessons.index',
                'icon' => 'bi-list-nested',
                'label' => 'Bài học (khung CTĐT)',
                'desc' => 'Danh sách bài / Unit; import khung chương trình theo ngành',
                'perm' => 'subject-lessons.index',
                'iconBg' => 'bg-teal-100 text-teal-800',
                'scope' => 'both',
            ],
        ];

        $visibleMenu = collect($menuItems)->filter(function (array $item) use ($user, $isFaculty, $isSuper, $unitIsPdot) {
            if (! empty($item['hidden'])) {
                return false;
            }

            if (! $user || ! $user->can($item['perm'])) {
                return false;
            }

            $scope = $item['scope'] ?? 'both';

            if ($isSuper || $scope === 'both') {
                return true;
            }

            if ($scope === 'training_office') {
                return $unitIsPdot || $isSuper;
            }

            if ($scope === 'faculty') {
                return $isFaculty || $isSuper;
            }

            return true;
        })->values()->all();

        return view('training-schedule::hub.index', [
            'stats' => $stats,
            'menuItems' => $visibleMenu,
            'user' => $user,
            'scopeLabel' => TrainingDept::scopeLabel($user),
            'isTrainingOffice' => $isTrainingOffice,
            'isFacultyManager' => $isFaculty,
        ]);
    }
}
