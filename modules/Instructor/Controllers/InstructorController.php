<?php

namespace Modules\Instructor\Controllers;

use App\Http\Controllers\ModuleBaseController;
use App\Models\User;
use App\Support\ManagerUnitScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Instructor\Models\Instructor;
use Modules\Instructor\Requests\CreateInstructorRequest;
use Modules\Instructor\Requests\UpdateInstructorRequest;
use Modules\Specialization\Models\Specialization;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Role;

class InstructorController extends ModuleBaseController
{
    /**
     * Display a listing of instructors
     */
    public function index(Request $request)
    {
        // Permission already checked by middleware
        $query = Instructor::with(['creator', 'specialization', 'unit']);

        // Apply search filter
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%')
                    ->orWhere('phone', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            });
        }

        // Apply specialization filter
        if ($request->filled('specialization_id')) {
            $query->where('specialization_id', $request->specialization_id);
        }
        // Apply unit filter
        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        // Manager: only instructors in managed khoa
        ManagerUnitScope::applyToInstructorQuery($query);

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Get per page value (default 10, allow 5, 10, 15, 25, 50)
        $perPage = $request->get('per_page', 10);
        $allowedPerPage = [5, 10, 15, 25, 50];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $instructors = $query->paginate($perPage)->withQueryString();

        // Get filter options
        $specializations = Specialization::where('is_active', true)
            ->with('trainingSystem:id,name')
            ->orderBy('name')
            ->get();
        $unitsQuery = Unit::where([
            'status' => 'active',
            'level' => 2,
        ]);
        if (ManagerUnitScope::isScoped()) {
            $unitsQuery->whereIn('id', ManagerUnitScope::managedUnitIds());
        }
        $units = $unitsQuery->get();

        return view('instructor::index', compact('instructors', 'specializations', 'units'));
    }

    /**
     * Show the form for creating a new instructor
     */
    public function create()
    {
        // Permission already checked by middleware

        $specializations = Specialization::where('is_active', true)
            ->with('trainingSystem:id,name')
            ->orderBy('name')
            ->get();

        // get all units with level  = 2
        $units = Unit::Where('level', 2)->get();

        return view('instructor::create', compact('specializations', 'units'));
    }

    /**
     * Store a newly created instructor
     */
    public function store(CreateInstructorRequest $request)
    {
        // Permission already checked by middleware
        try {
            DB::beginTransaction();

            $data = $request->validated();
            if (ManagerUnitScope::isScoped()) {
                $data['unit_id'] = ManagerUnitScope::managedUnitId();
            }
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $instructor = Instructor::create($data);

            DB::commit();

            return redirect()
                ->route('instructors.index')
                ->with('success', 'Giảng viên đã được tạo thành công!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo giảng viên: '.$e->getMessage());
        }
    }

    /**
     * Display the specified instructor
     */
    public function show(Instructor $instructor)
    {
        // Permission already checked by middleware
        ManagerUnitScope::ensureCanAccessInstructor($instructor);

        $instructor->load(['creator', 'updater']);

        return view('instructor::show', compact('instructor'));
    }

    /**
     * Show the form for editing the specified instructor
     */
    public function edit(Instructor $instructor)
    {
        // Permission already checked by middleware
        ManagerUnitScope::ensureCanAccessInstructor($instructor);

        $specializations = Specialization::where('is_active', true)
            ->with('trainingSystem:id,name')
            ->orderBy('name')
            ->get();
        // get all units with level  = 2
        $units = Unit::Where('level', 2)->get();

        return view('instructor::edit', compact('instructor', 'specializations', 'units'));
    }

    /**
     * Update the specified instructor
     */
    public function update(UpdateInstructorRequest $request, Instructor $instructor)
    {
        // Permission already checked by middleware
        ManagerUnitScope::ensureCanAccessInstructor($instructor);
        try {
            DB::beginTransaction();

            $data = $request->validated();
            if (ManagerUnitScope::isScoped()) {
                $data['unit_id'] = ManagerUnitScope::managedUnitId();
            }
            $data['updated_by'] = Auth::id();

            $instructor->update($data);

            // SĐT phải đồng bộ với tài khoản đăng nhập liên kết (nếu có) —
            // sửa ở bên nào cũng phản ánh sang bên kia.
            if (! empty($data['phone'])) {
                User::query()->where('instructor_id', $instructor->id)->update(['phone' => $data['phone']]);
            }

            DB::commit();

            return redirect()
                ->route('instructors.index')
                ->with('success', 'Thông tin giảng viên đã được cập nhật thành công!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật giảng viên: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified instructor
     */
    public function destroy(Instructor $instructor)
    {
        // Permission already checked by middleware
        ManagerUnitScope::ensureCanAccessInstructor($instructor);
        try {
            DB::beginTransaction();

            $instructor->delete();

            DB::commit();

            return redirect()
                ->route('instructors.index')
                ->with('success', 'Giảng viên đã được xóa thành công!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'Có lỗi xảy ra khi xóa giảng viên: '.$e->getMessage());
        }
    }

    /**
     * Restore the specified instructor
     */
    public function restore($id)
    {
        // Permission already checked by middleware
        $instructor = Instructor::withTrashed()->findOrFail($id);
        $instructor->restore();

        return redirect()
            ->route('instructors.index')
            ->with('success', 'Giảng viên đã được khôi phục thành công!');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(Instructor $instructor)
    {
        // Permission already checked by middleware
        $instructor->update([
            'status' => $instructor->status === 'active' ? 'inactive' : 'active',
            'updated_by' => Auth::id(),
        ]);

        $status = $instructor->status === 'active' ? 'kích hoạt' : 'tạm ngừng';

        return redirect()
            ->back()
            ->with('success', "Giảng viên đã được {$status} thành công!");
    }

    /**
     * Export instructors to CSV
     */
    public function export(Request $request)
    {
        // Permission already checked by middleware
        $query = Instructor::with(['creator']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%')
                    ->orWhere('phone', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $instructors = $query->get();

        $filename = 'instructors_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($instructors) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Họ và tên',
                'Email',
                'Số điện thoại',
                'Khoa',
                'Trạng thái',
                'Người tạo',
                'Ngày tạo',
                'Cập nhật cuối',
            ]);

            // CSV data
            foreach ($instructors as $instructor) {
                fputcsv($file, [
                    $instructor->name,
                    $instructor->email,
                    $instructor->phone,
                    $instructor->department,
                    $instructor->status_label,
                    $instructor->creator->name ?? 'N/A',
                    $instructor->created_at->format('d/m/Y H:i'),
                    $instructor->updated_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Create a user account for an instructor
     */
    public function createUserAccount(Instructor $instructor)
    {
        ManagerUnitScope::ensureCanAccessInstructor($instructor);

        // Check if instructor already has a user account
        if ($instructor->hasUserAccount()) {
            return back()->with('error', 'Giảng viên này đã có tài khoản đăng nhập.');
        }

        // Get default role for instructors (you may want to make this configurable)
        $defaultRole = Role::where('name', 'instructor')->first();
        if (! $defaultRole) {
            $defaultRole = Role::where('name', 'user')->first();
        }

        return view('instructor::create-user-account', compact('instructor', 'defaultRole'));
    }

    /**
     * Store the user account for an instructor
     */
    public function storeUserAccount(Request $request, Instructor $instructor)
    {
        ManagerUnitScope::ensureCanAccessInstructor($instructor);

        // Check if instructor already has a user account
        if ($instructor->hasUserAccount()) {
            return back()->with('error', 'Giảng viên này đã có tài khoản đăng nhập.');
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:6'],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('name', 'instructor')),
            ],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'role_id.required' => 'Vui lòng chọn vai trò.',
            'role_id.exists' => 'Vai trò không tồn tại.',
        ]);

        try {
            DB::beginTransaction();

            // Create user account
            $user = User::create([
                'name' => $instructor->name,
                'code' => $instructor->code,
                'email' => $validated['email'],
                'phone' => $instructor->phone,
                'password' => bcrypt($validated['password']),
                'instructor_id' => $instructor->id,
                'unit_id' => $instructor->unit_id,
                'user_type' => 'instructor',
                'status' => $instructor->status === 'active' ? 1 : 0,
            ]);

            // Assign role
            $role = Role::find($validated['role_id']);
            if ($role) {
                $user->syncRoles([$role->name]);
                $user->update(['role_id' => $role->id]);
            }

            DB::commit();

            return redirect()
                ->route('instructors.show', $instructor)
                ->with('success', 'Tài khoản đăng nhập cho giảng viên đã được tạo thành công!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo tài khoản: '.$e->getMessage());
        }
    }
}
