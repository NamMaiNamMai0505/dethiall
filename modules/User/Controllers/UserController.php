<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\ModuleBaseController;
use App\Models\MilitaryRank;
use App\Models\User;
use App\Support\ManagerPosition;
use App\Support\MilitaryRankAssignment;
use App\Support\RoleAssignment;
use App\Support\RoleDisplay;
use App\Support\RoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\LeaveManagement\Models\LeavePosition;
use Modules\LeaveManagement\Models\LeavePersonnel;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Models\Position;
use Modules\Unit\Models\Unit;
use Modules\User\Exports\UsersTemplateExport;
use Modules\User\Requests\CreateUserRequest;
use Modules\User\Requests\UpdateProfileRequest;
use Modules\User\Requests\UpdateUserRequest;
use Spatie\Permission\Models\Role;

class UserController extends ModuleBaseController
{
    /**
     * Display the authenticated user's profile
     */
    public function profile()
    {
        $user = Auth::user();
        $user->load(['unit', 'roleRelation', 'militaryRank', 'class', 'instructor']);

        return view('user::profile', compact('user'));
    }

    /**
     * Update authenticated user name + password (all roles: instructor, student, internal).
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        $user->name = $data['name'];

        if (! empty($data['password'])) {
            $user->password = $data['password']; // cast hashed on User model
        }

        $user->save();

        // Đồng bộ họ tên sang hồ sơ giảng viên nếu có liên kết
        if ($user->instructor_id && $user->instructor) {
            $user->instructor->update(['name' => $user->name]);
        }

        return redirect()
            ->route('profile')
            ->with('success', 'Đã cập nhật thông tin tài khoản.');
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        // Permission already checked by middleware
        $query = User::with(['unit', 'roleRelation', 'militaryRank'])
            ->where(function ($q) {
                $q->where('user_type', '!=', 'student')
                    ->orWhereNull('user_type');
            });

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }
        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }
        if ($request->filled('sync_status')) {
            if ($request->sync_status === 'unsynced') {
                // Show instructors without instructor_id
                $query->where('user_type', 'instructor')
                    ->whereNull('instructor_id');
            } elseif ($request->sync_status === 'synced') {
                // Show instructors with instructor_id
                $query->where('user_type', 'instructor')
                    ->whereNotNull('instructor_id');
            }
        }
        // sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';
        $users = $query->orderBy($sortBy, $sortOrder)
            ->paginate(15)
            ->appends($request->all());
        $units = Unit::orderBy('name')->pluck('name', 'id');

        // Check if current user can delete users
        $canDelete = Auth::check() && Auth::user()->can('users.delete');

        return view('user::index', compact(
            'users',
            'units',
            'canDelete'
        ));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        // Permission already checked by middleware
        $units = $this->unitOptions();
        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Role $role) => [$role->id => RoleDisplay::label($role->name)]);
        $instructors = Instructor::active()
            ->withoutUserAccount()
            ->with('unit')
            ->get();
        $classes = ClassModel::pluck('name', 'id');
        $formExtras = $this->userFormExtras();
        $militaryLinkRoleIds = $this->militaryLinkRoleIds();
        $militaryPersonnel = LeavePersonnel::withoutGlobalScopes()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'staff_code', 'name', 'rank', 'position', 'unit', 'email', 'gmail', 'user_id']);
        $leavePositions = $this->leavePositionOptions();

        return view('user::create', array_merge(
            compact('units', 'roles', 'instructors', 'classes', 'militaryLinkRoleIds', 'militaryPersonnel', 'leavePositions'),
            $formExtras
        ));
    }

    /**
     * Store a newly created user
     */
    public function store(CreateUserRequest $request)
    {
        // Permission already checked by middleware

        $data = $request->validated();
        $leavePersonnelId = $data['leave_personnel_id'] ?? null;
        $leavePosition = $data['leave_position'] ?? null;
        unset($data['leave_position']);
        $linkedPersonnel = ! empty($data['leave_personnel_id'])
            ? LeavePersonnel::withoutGlobalScopes()->where('active', true)->findOrFail($data['leave_personnel_id'])
            : null;
        if ($linkedPersonnel?->user_id) {
            abort(422, 'Hồ sơ quân nhân này đã được liên kết với một tài khoản khác. Hãy gỡ liên kết trước khi tạo tài khoản mới.');
        }
        if ($linkedPersonnel) {
            // Hồ sơ quân nhân là nguồn dữ liệu ưu tiên; trường nào hồ sơ chưa có
            // thì giữ giá trị người quản trị nhập trên form.
            $data['name'] = $linkedPersonnel->name ?: $data['name'];
            $data['code'] = $linkedPersonnel->staff_code ?: ($data['code'] ?? null);
            $data['email'] = $linkedPersonnel->gmail ?: ($linkedPersonnel->email ?: $data['email']);
        }
        $data['password'] = bcrypt($data['password']);
        $data['user_type'] = $data['user_type'] ?? 'internal_user';
        $data['position_id'] = $data['position_id'] ?? null;
        $data['object_type_id'] = $data['object_type_id'] ?? null;
        $data['military_rank_id'] = MilitaryRankAssignment::allows(
            (int) $data['role_id'],
            $data['user_type'] ?? null
        )
            ? ($data['military_rank_id'] ?? null)
            : null;
        $user = User::create($data);

        // Get role by ID and sync using role name
        $role = Role::find($data['role_id']);
        if ($role) {
            $user->syncRoles([$role->name]);
        }

        $this->syncMilitaryPersonnelLink($user, $leavePersonnelId);
        $this->syncMilitaryPersonnelPosition($leavePersonnelId, $leavePosition);

        $this->syncInstructorStandardHours($user, $data);

        return redirect()->route('users.index')
            ->with('success', 'Người dùng đã được tạo thành công!');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        // Permission already checked by middleware

        $user->load(['unit', 'roleRelation', 'militaryRank']);

        return view('user::show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        // Permission already checked by middleware

        $units = $this->unitOptions();
        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Role $role) => [$role->id => RoleDisplay::label($role->name)]);

        // Get instructors without user accounts, plus the current instructor if this user is linked to one
        $instructors = Instructor::active()
            ->where(function ($query) use ($user) {
                $query->withoutUserAccount()
                    ->orWhere('id', $user->instructor_id);
            })
            ->get()
            ->pluck('name', 'id');

        $classes = ClassModel::pluck('name', 'id');
        $user->loadMissing(['instructor']);
        $militaryPersonnel = LeavePersonnel::withoutGlobalScopes()->where('active', true)->orderBy('name')->get(['id', 'staff_code', 'name', 'rank', 'position', 'unit', 'email', 'gmail', 'user_id']);
        $selectedLeavePersonnelId = LeavePersonnel::withoutGlobalScopes()->where('user_id', $user->id)->value('id');
        $formExtras = $this->userFormExtras($user);
        $leavePositions = $this->leavePositionOptions();

        // Ưu tiên giá trị user; fallback hồ sơ GV (để edit thấy đúng Chức danh / Đối tượng)
        $selectedPositionId = old('position_id', $user->position_id ?: $user->instructor?->position_id);
        $selectedObjectTypeId = old('object_type_id', $user->object_type_id ?: $user->instructor?->object_type_id);

        return view('user::edit', array_merge(
            compact('user', 'units', 'roles', 'instructors', 'classes', 'militaryPersonnel', 'selectedLeavePersonnelId', 'selectedPositionId', 'selectedObjectTypeId', 'leavePositions'),
            $formExtras
        ));
    }

    /**
     * Dữ liệu form dùng chung create/edit: chức danh + đối tượng giờ chuẩn + role.
     *
     * @return array{
     *     managerPositions: array<int, string>,
     *     objectTypes: array<int, string>,
     *     managerPositionIds: list<int>,
     *     managerRoleId: int|null,
     *     instructorRoleId: int|null,
     *     unitRequiredRoleIds: list<int>,
     *     militaryRanks: array<int, string>,
     *     rankEligibleRoleIds: list<int>
     * }
     */
    private function userFormExtras(?User $user = null): array
    {
        $managerPositions = Position::query()
            ->active()
            ->orderBy('ratio_percent')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Position $p) => [
                $p->id => $p->name.' ('.number_format((float) $p->ratio_percent, 0).'%)',
            ])
            ->toArray();

        // Giữ option đang chọn dù đã tắt (gộp tên cũ)
        $keepPositionId = $user?->position_id ?: $user?->instructor?->position_id;
        if ($keepPositionId && ! isset($managerPositions[$keepPositionId])) {
            $kept = Position::withTrashed()->find($keepPositionId);
            if ($kept) {
                $managerPositions[$kept->id] = $kept->name.' ('.number_format((float) $kept->ratio_percent, 0).'%) — cũ';
            }
        }

        if ($managerPositions === []) {
            $managerPositions = ManagerPosition::options();
        }

        $objectTypes = ObjectType::query()
            ->active()
            ->orderBy('code')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (ObjectType $o) => [
                $o->id => ($o->code ? $o->code.' — ' : '').$o->name
                    .' · GC '.number_format((float) $o->standard_hours, 0)
                    .' · NCKH '.number_format((float) $o->research_hours, 0)
                    .' · HC '.number_format((float) $o->administrative_hours, 0),
            ])
            ->toArray();

        $keepObjectTypeId = $user?->object_type_id ?: $user?->instructor?->object_type_id;
        if ($keepObjectTypeId && ! isset($objectTypes[$keepObjectTypeId])) {
            $keptOt = ObjectType::withTrashed()->find($keepObjectTypeId);
            if ($keptOt) {
                $objectTypes[$keptOt->id] = ($keptOt->code ? $keptOt->code.' — ' : '').$keptOt->name.' — cũ';
            }
        }

        $managerPositionIds = array_keys(ManagerPosition::options());
        $managerRoleId = Role::query()->where('name', 'manager')->value('id');
        $instructorRoleId = Role::query()->where('name', 'instructor')->value('id');
        $unitRequiredRoleIds = RoleAssignment::unitRequiredRoleIds();
        $rankEligibleRoleIds = MilitaryRankAssignment::eligibleRoleIds();
        $militaryRanks = MilitaryRank::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (MilitaryRank $rank) => [
                $rank->id => $rank->group_name.' · '.$rank->display_name,
            ])
            ->all();

        return compact(
            'managerPositions',
            'objectTypes',
            'managerPositionIds',
            'managerRoleId',
            'instructorRoleId',
            'unitRequiredRoleIds',
            'militaryRanks',
            'rankEligibleRoleIds'
        );
    }

    /** @return list<int> */
    private function militaryLinkRoleIds(): array
    {
        return Role::query()->get(['id', 'name'])
            ->filter(function (Role $role): bool {
                $name = Str::lower(Str::ascii((string) $role->name));
                return Str::contains($name, ['quan nhan', 'chi huy co quan', 'quan luc', 'co quan can bo']);
            })
            ->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        return Role::query()
            ->whereIn('name', [
                RoleCatalog::LEAVE_MILITARY,
                RoleCatalog::LEAVE_COMMANDER,
                RoleCatalog::LEAVE_QUAN_LUC,
                RoleCatalog::LEAVE_MANAGEMENT_AGENCY,
            ])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function syncMilitaryPersonnelLink(User $user, ?int $personnelId): void
    {
        if (! $personnelId) {
            LeavePersonnel::withoutGlobalScopes()->where('user_id', $user->id)->update(['user_id' => null]);
            return;
        }

        LeavePersonnel::withoutGlobalScopes()->where('user_id', $user->id)->where('id', '!=', $personnelId)->update(['user_id' => null]);

        $personnel = LeavePersonnel::withoutGlobalScopes()
            ->whereKey($personnelId)
            ->where('active', true)
            ->first();
        if (! $personnel) {
            return;
        }

        $update = ['user_id' => $user->id];
        if (blank($personnel->email)) $update['email'] = $user->email;
        if (blank($personnel->gmail)) $update['gmail'] = $user->email;
        if (blank($personnel->staff_code) && filled($user->code)) $update['staff_code'] = $user->code;
        $personnel->update($update);
    }

    private function syncMilitaryPersonnelPosition(?int $personnelId, ?string $position): void
    {
        if (! $personnelId || $position === null) {
            return;
        }

        LeavePersonnel::withoutGlobalScopes()
            ->whereKey($personnelId)
            ->where('active', true)
            ->update(['position' => $position ?: null]);
    }

    /** @return array<string, string> */
    private function leavePositionOptions(): array
    {
        return LeavePosition::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();
    }

    /** @return array<int, string> */
    private function unitOptions(): array
    {
        return Unit::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (Unit $unit): array {
                $scope = $unit->functional_type_label;
                if ($unit->faculty_code) {
                    $scope .= ' · '.$unit->faculty_code;
                }

                return [$unit->id => $unit->name.' — '.$scope];
            })
            ->all();
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // Permission already checked by middleware

        $data = $request->validated();
        $leavePersonnelId = $data['leave_personnel_id'] ?? null;
        $leavePosition = $data['leave_position'] ?? null;
        unset($data['leave_position']);
        if (! empty($data['leave_personnel_id'])) {
            $linkedPersonnel = LeavePersonnel::withoutGlobalScopes()->where('active', true)->findOrFail($data['leave_personnel_id']);
            abort_unless(! $linkedPersonnel->user_id || (int) $linkedPersonnel->user_id === (int) $user->id, 422, 'Hồ sơ quân nhân này đã được liên kết với một tài khoản khác.');
        }
        if (! empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        // Luôn ghi nhận chức danh / đối tượng (kể cả null khi clear)
        $data['position_id'] = $data['position_id'] ?? null;
        $data['object_type_id'] = $data['object_type_id'] ?? null;
        $data['military_rank_id'] = MilitaryRankAssignment::allows(
            (int) $data['role_id'],
            $data['user_type'] ?? null
        )
            ? ($data['military_rank_id'] ?? null)
            : null;

        $user->update($data);

        // Get role by ID and sync using role name
        $role = Role::find($data['role_id']);
        if ($role) {
            $user->syncRoles([$role->name]);
        }

        $this->syncMilitaryPersonnelLink($user, $leavePersonnelId);
        $this->syncMilitaryPersonnelPosition($leavePersonnelId, $leavePosition);

        $this->syncInstructorStandardHours($user->fresh(), $data);

        return redirect()->route('users.index')
            ->with('success', 'Người dùng đã được cập nhật thành công!');
    }

    /**
     * Đồng bộ Đối tượng + Chức danh từ user → hồ sơ GV (dùng khi tính giờ chuẩn).
     * Định mức phải đạt = object_type.standard_hours × position.ratio_percent.
     */
    private function syncInstructorStandardHours(User $user, array $data): void
    {
        $instructorId = $user->instructor_id ?: ($data['instructor_id'] ?? null);
        if (! $instructorId) {
            return;
        }

        $instructorUpdate = [
            'position_id' => $data['position_id'] ?? null,
            'object_type_id' => $data['object_type_id'] ?? null,
        ];

        // SĐT phải đồng bộ giữa tài khoản và hồ sơ GV liên kết — sửa ở bên
        // nào cũng phản ánh sang bên kia (single source of truth).
        if (array_key_exists('phone', $data) && $data['phone'] !== null && $data['phone'] !== '') {
            $instructorUpdate['phone'] = $data['phone'];
        }

        Instructor::query()->whereKey($instructorId)->update($instructorUpdate);
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Permission already checked by middleware

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Người dùng đã được xóa thành công!');
    }

    /**
     * Bulk delete multiple users
     */
    public function bulkDestroy(Request $request)
    {
        // Permission already checked by middleware

        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $request->user_ids;
        $currentUserId = Auth::id();

        // Filter out the current user ID, super admin users, and students to prevent deletion
        $usersToDelete = User::whereIn('id', $userIds)
            ->where('id', '!=', $currentUserId)
            ->where(function ($q) {
                $q->where('user_type', '!=', 'student')
                    ->orWhereNull('user_type');
            })
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'super-admin');
            })
            ->get();

        if ($usersToDelete->isEmpty()) {
            return redirect()->route('users.index')
                ->with('error', 'Không có người dùng nào có thể xóa được từ danh sách đã chọn.');
        }

        $deletedCount = $usersToDelete->count();
        $totalSelected = count($userIds);

        // Delete the users
        User::whereIn('id', $usersToDelete->pluck('id'))->delete();

        $message = "Đã xóa thành công {$deletedCount} người dùng.";
        if ($deletedCount < $totalSelected) {
            $skipped = $totalSelected - $deletedCount;
            $message .= " Đã bỏ qua {$skipped} người dùng không thể xóa.";
        }

        return redirect()->route('users.index')
            ->with('success', $message);
    }

    /**
     * Bulk sync instructor accounts
     */
    public function bulkSyncInstructor(Request $request)
    {
        // Validate user_ids
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $request->user_ids;

        // Get only instructor users
        $users = User::whereIn('id', $userIds)
            ->where('user_type', 'instructor')
            ->get();

        $syncedCount = 0;
        $createdCount = 0;
        $skippedCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($users as $user) {
                // Check if already synced
                if ($user->instructor_id) {
                    $skippedCount++;

                    continue;
                }

                // Validate user has required data
                if (! $user->unit_id) {
                    $errors[] = [
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'error' => 'Người dùng chưa có đơn vị, không thể tạo giảng viên',
                    ];

                    continue;
                }

                // Find matching instructor by email or code
                $instructor = Instructor::where('email', $user->email)
                    ->orWhere('code', $user->code)
                    ->first();

                if ($instructor) {
                    // Case 1: Found instructor - sync/update and link
                    $existingUser = User::where('instructor_id', $instructor->id)
                        ->where('id', '!=', $user->id)
                        ->first();

                    if ($existingUser) {
                        $errors[] = [
                            'user_name' => $user->name,
                            'user_email' => $user->email,
                            'error' => "Giảng viên {$instructor->code} đã có tài khoản đăng nhập khác",
                        ];
                    } else {
                        // Update instructor info from user
                        $instructor->update([
                            'name' => $user->name,
                            'unit_id' => $user->unit_id,
                            'code' => $user->code ?? $instructor->code,
                            'updated_by' => auth()->id(),
                        ]);

                        // Link user to instructor
                        $user->update(['instructor_id' => $instructor->id]);
                        $syncedCount++;
                    }
                } else {
                    // Case 2: Not found - create new instructor and link
                    try {
                        $newInstructor = Instructor::create([
                            'name' => $user->name,
                            'code' => $user->code,
                            'email' => $user->email,
                            'unit_id' => $user->unit_id,
                            'status' => 'active',
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);

                        // Link user to new instructor
                        $user->update(['instructor_id' => $newInstructor->id]);
                        $createdCount++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'user_name' => $user->name,
                            'user_email' => $user->email,
                            'error' => 'Không thể tạo giảng viên: '.$e->getMessage(),
                        ];
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'synced_count' => $syncedCount,
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $fileName = 'template_import_users_'.date('Y-m-d').'.xlsx';

        return Excel::download(new UsersTemplateExport, $fileName);
    }

    /**
     * Import users and students from array data
     */
    public function import(Request $request)
    {
        // Validate request
        $request->validate([
            'users' => 'required|array|min:1',
            'users.*.type' => 'required|string|in:user,student',
            'users.*.name' => 'required|string|max:255',
            'users.*.code' => 'nullable|string|max:255',
            'users.*.email' => 'required|email|max:255',
            // Các cột dưới có thể trống — form class_setup / default sẽ bù
            'users.*.password' => 'nullable|string',
            'users.*.unit_name' => 'nullable|string',
            'users.*.role_name' => 'nullable|string',
            'users.*.user_type' => 'nullable|string',
            'users.*.class_code' => 'nullable|string',
            'class_setup' => 'nullable|array',
            'class_setup.unit_id' => 'nullable|exists:units,id',
            'class_setup.specialization_id' => 'nullable|exists:specializations,id',
            'class_setup.management_unit' => 'nullable|string|max:255',
            'class_setup.classroom_id' => 'nullable|exists:classrooms,id',
            'class_setup.class_name' => 'nullable|string|max:255',
            'class_setup.class_code' => 'nullable|string|max:100',
            'class_setup.instructor_id' => 'nullable|exists:instructors,id',
            'class_setup.start_date' => 'nullable|date',
            'class_setup.end_date' => 'nullable|date|after_or_equal:class_setup.start_date',
            'class_setup.duration_months' => 'nullable|integer|min:1|max:120',
            'class_setup.max_students' => 'nullable|integer|min:1|max:5000',
        ]);

        $usersData = $request->input('users', []);
        $classSetup = $request->input('class_setup') ?: [];
        $importedUsers = 0;
        $importedStudents = 0;
        $updatedCount = 0;
        $syncedInstructors = 0;
        $classCreatedOrUpdated = null;
        $errors = [];

        try {
            DB::beginTransaction();

            // Chuẩn bị lớp học khi import chỉ học viên (class_setup form ngoài file)
            $sharedClassId = null;
            $sharedUnitId = null;
            $studentRows = collect($usersData)->filter(function ($row) {
                return ($row['type'] ?? '') === 'student' || ($row['user_type'] ?? '') === 'student';
            });
            $hasClassSetup = filled($classSetup['class_code'] ?? null)
                || filled($classSetup['class_name'] ?? null)
                || filled($classSetup['unit_id'] ?? null);

            if ($hasClassSetup) {
                $studentCount = max(1, $studentRows->count());
                $maxStudents = (int) ($classSetup['max_students'] ?? $studentCount);
                if ($maxStudents < $studentCount) {
                    $maxStudents = $studentCount;
                }

                $classCode = trim((string) ($classSetup['class_code'] ?? ''));
                $className = trim((string) ($classSetup['class_name'] ?? $classCode));

                if ($classCode === '' || $className === '') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vui lòng nhập đủ Tên lớp và Mã lớp khi import học viên.',
                    ], 422);
                }

                if (blank($classSetup['specialization_id'] ?? null)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vui lòng chọn ngành đào tạo cho lớp học viên.',
                    ], 422);
                }

                if (blank($classSetup['unit_id'] ?? null)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vui lòng chọn khoa/đơn vị cho lớp học viên.',
                    ], 422);
                }

                $sharedUnitId = (int) $classSetup['unit_id'];

                $classPayload = [
                    'name' => $className,
                    'code' => $classCode,
                    'specialization_id' => (int) $classSetup['specialization_id'],
                    'instructor_id' => filled($classSetup['instructor_id'] ?? null) ? (int) $classSetup['instructor_id'] : null,
                    'classroom_id' => filled($classSetup['classroom_id'] ?? null) ? (int) $classSetup['classroom_id'] : null,
                    'management_unit' => filled($classSetup['management_unit'] ?? null) ? $classSetup['management_unit'] : null,
                    'start_date' => filled($classSetup['start_date'] ?? null) ? $classSetup['start_date'] : null,
                    'end_date' => filled($classSetup['end_date'] ?? null) ? $classSetup['end_date'] : null,
                    'duration_months' => (int) ($classSetup['duration_months'] ?? 0) ?: null,
                    'max_students' => $maxStudents,
                    'current_students' => $studentCount,
                    'is_active' => true,
                    'updated_by' => Auth::id(),
                ];

                $class = ClassModel::withTrashed()
                    ->where('code', $classCode)
                    ->first();

                if ($class) {
                    if ($class->trashed()) {
                        $class->restore();
                    }
                    $class->update($classPayload);
                    $classCreatedOrUpdated = 'updated';
                } else {
                    $classPayload['created_by'] = Auth::id();
                    $class = ClassModel::create($classPayload);
                    $classCreatedOrUpdated = 'created';
                }

                $sharedClassId = $class->id;
            }

            foreach ($usersData as $index => $userData) {
                $rowNumber = $index + 2; // +2 because Excel starts at 1 and has header row

                try {
                    // Cột trống trong file = bỏ qua, dùng form class_setup / default
                    $name = trim((string) ($userData['name'] ?? ''));
                    $email = trim((string) ($userData['email'] ?? ''));
                    $code = filled($userData['code'] ?? null) ? trim((string) $userData['code']) : null;
                    $unitName = filled($userData['unit_name'] ?? null) ? trim((string) $userData['unit_name']) : null;
                    $roleNameRaw = filled($userData['role_name'] ?? null) ? trim((string) $userData['role_name']) : null;
                    $userTypeRaw = filled($userData['user_type'] ?? null) ? trim((string) $userData['user_type']) : null;
                    $classCodeFile = filled($userData['class_code'] ?? null) ? trim((string) $userData['class_code']) : null;
                    $passwordRaw = filled($userData['password'] ?? null) ? (string) $userData['password'] : null;

                    $isStudent = ($userData['type'] ?? '') === 'student'
                        || $userTypeRaw === 'student';

                    if ($name === '' || $email === '') {
                        $errors[] = [
                            'row' => $rowNumber,
                            'message' => 'Thiếu họ tên hoặc email (bắt buộc trong file).',
                        ];

                        continue;
                    }

                    // Unit: form class_setup trước; cột Đơn vị chỉ dùng khi có giá trị
                    $unit = null;
                    if ($sharedUnitId) {
                        $unit = Unit::find($sharedUnitId);
                    }
                    if (! $unit && $unitName) {
                        $unit = Unit::where('name', $unitName)->first();
                    }
                    if (! $unit) {
                        // Cột trống + không có form → mới báo lỗi
                        $errors[] = [
                            'row' => $rowNumber,
                            'message' => 'Không xác định được đơn vị/khoa (cột Đơn vị trống và chưa chọn ở form import).',
                        ];

                        continue;
                    }

                    // Role: cột trống → student (HV) hoặc bỏ qua lỗi nếu map được
                    $roleName = $this->resolveImportRoleName($roleNameRaw, $isStudent);
                    $role = $roleName ? Role::where('name', $roleName)->first() : null;
                    if (! $role && $isStudent) {
                        $role = Role::where('name', 'student')->first();
                        $roleName = 'student';
                    }
                    if (! $role) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'message' => 'Không tìm thấy vai trò: '.($roleNameRaw ?: $roleName ?: '(trống)'),
                        ];

                        continue;
                    }

                    // Class for students: form trước; cột Mã lớp chỉ khi có giá trị và không có form
                    $classId = $sharedClassId;
                    if ($isStudent && ! $classId) {
                        if (! $classCodeFile) {
                            $errors[] = [
                                'row' => $rowNumber,
                                'message' => 'Cột Mã lớp trống — hãy điền form cấu hình lớp hoặc nhập mã lớp trong file.',
                            ];

                            continue;
                        }

                        $class = ClassModel::where('code', $classCodeFile)->first();
                        if (! $class) {
                            $errors[] = [
                                'row' => $rowNumber,
                                'message' => "Không tìm thấy lớp: {$classCodeFile}",
                            ];

                            continue;
                        }
                        $classId = $class->id;
                    }

                    // Password trống → default
                    $password = $passwordRaw ?: 'password';

                    // user_type trống → suy từ loại dòng / form
                    $userType = $userTypeRaw ?: ($isStudent ? 'student' : 'internal_user');
                    if ($isStudent) {
                        $userType = 'student';
                    }

                    // Check if user already exists by email (ignore soft-deleted)
                    $existingUser = User::where('email', $email)->first();

                    if ($existingUser) {
                        // Cập nhật: cột trống thì giữ giá trị cũ (không ghi đè null)
                        $updateData = [
                            'name' => $name,
                            'unit_id' => $unit->id,
                            'user_type' => $userType,
                        ];

                        if ($code !== null) {
                            $updateData['code'] = $code;
                        }

                        // Chỉ đổi mật khẩu khi file có nhập (không trống)
                        if ($passwordRaw !== null) {
                            $updateData['password'] = bcrypt($passwordRaw);
                        }

                        if ($userType === 'student' && $classId) {
                            $updateData['class_id'] = $classId;
                        }

                        $existingUser->update($updateData);

                        // Update role
                        $existingUser->syncRoles([$role->name]);
                        $existingUser->update(['role_id' => $role->id]);

                        // Auto-sync instructor if applicable
                        if ($userType === 'instructor' && ! $existingUser->instructor_id) {
                            $synced = $this->autoSyncInstructor($existingUser);
                            if ($synced) {
                                $syncedInstructors++;
                            }
                        }

                        $updatedCount++;
                    } else {
                        // Create new user
                        $createData = [
                            'name' => $name,
                            'code' => $code,
                            'email' => $email,
                            'password' => bcrypt($password),
                            'unit_id' => $unit->id,
                            'user_type' => $userType,
                            'status' => 1,
                        ];

                        if ($userType === 'student' && $classId) {
                            $createData['class_id'] = $classId;
                        }

                        $user = User::create($createData);

                        // Assign role
                        $user->syncRoles([$role->name]);
                        $user->update(['role_id' => $role->id]);

                        // Auto-sync instructor if applicable
                        if ($userType === 'instructor') {
                            $synced = $this->autoSyncInstructor($user);
                            if ($synced) {
                                $syncedInstructors++;
                            }
                        }

                        if ($userType === 'student') {
                            $importedStudents++;
                        } else {
                            $importedUsers++;
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            // Cập nhật quân số lớp sau import
            if ($sharedClassId) {
                $countInClass = User::query()
                    ->where('class_id', $sharedClassId)
                    ->where('user_type', 'student')
                    ->count();
                $classModel = ClassModel::find($sharedClassId);
                if ($classModel) {
                    $classModel->update([
                        'current_students' => $countInClass,
                        'max_students' => max((int) $classModel->max_students, $countInClass),
                    ]);
                }
            }

            DB::commit();

            $message = '';
            if ($importedUsers > 0) {
                $message .= "Đã nhập thành công {$importedUsers} người dùng mới";
            }
            if ($importedStudents > 0) {
                if ($message) {
                    $message .= ', ';
                }
                $message .= "{$importedStudents} học viên mới";
            }
            if ($updatedCount > 0) {
                if ($message) {
                    $message .= ', ';
                }
                $message .= "cập nhật {$updatedCount} bản ghi đã tồn tại";
            }
            if ($syncedInstructors > 0) {
                if ($message) {
                    $message .= ', ';
                }
                $message .= "đồng bộ {$syncedInstructors} giảng viên";
            }
            if ($classCreatedOrUpdated === 'created') {
                $message .= ($message ? '; ' : '').'đã tạo lớp học mới';
            } elseif ($classCreatedOrUpdated === 'updated') {
                $message .= ($message ? '; ' : '').'đã cập nhật lớp học';
            }

            if (! $message) {
                $message = 'Không có dữ liệu nào được nhập';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'imported_users' => $importedUsers,
                    'imported_students' => $importedStudents,
                    'updated' => $updatedCount,
                    'synced_instructors' => $syncedInstructors,
                    'class_setup' => $classCreatedOrUpdated,
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: '.$e->getMessage(),
                'errors' => $errors,
            ], 500);
        }
    }

    /**
     * Map tên vai trò từ file Excel → role.name trong hệ thống.
     * Cột trống → default theo loại dòng (student / null).
     */
    private function resolveImportRoleName(?string $roleName, bool $isStudent): ?string
    {
        if (blank($roleName)) {
            return $isStudent ? 'student' : null;
        }

        $normalized = mb_strtolower(trim($roleName));
        $map = [
            'student' => 'student',
            'học viên' => 'student',
            'hoc vien' => 'student',
            'instructor' => 'instructor',
            'giảng viên' => 'instructor',
            'giang vien' => 'instructor',
            'super-admin' => 'super-admin',
            'super admin' => 'super-admin',
            'admin' => 'super-admin',
        ];

        return $map[$normalized] ?? $roleName;
    }

    /**
     * Auto-sync instructor account with instructor record
     * Creates new instructor if not found, syncs/updates if found
     * Returns true if synced/created successfully, false otherwise
     */
    private function autoSyncInstructor(User $user): bool
    {
        // Check if already synced
        if ($user->instructor_id) {
            return false;
        }

        // Validate user has required data
        if (! $user->unit_id) {
            return false;
        }

        // Find matching instructor by email or code
        $instructor = Instructor::where('email', $user->email)
            ->orWhere('code', $user->code)
            ->first();

        if ($instructor) {
            // Case 1: Found instructor - sync/update and link
            $existingUser = User::where('instructor_id', $instructor->id)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                return false;
            }

            // Update instructor info from user
            $instructor->update([
                'name' => $user->name,
                'unit_id' => $user->unit_id,
                'code' => $user->code ?? $instructor->code,
                'updated_by' => auth()->id(),
            ]);

            // Link user to instructor
            $user->update(['instructor_id' => $instructor->id]);

            return true;
        } else {
            // Case 2: Not found - create new instructor and link
            try {
                $newInstructor = Instructor::create([
                    'name' => $user->name,
                    'code' => $user->code,
                    'email' => $user->email,
                    'unit_id' => $user->unit_id,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                // Link user to new instructor
                $user->update(['instructor_id' => $newInstructor->id]);

                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
    }
}
