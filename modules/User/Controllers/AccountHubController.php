<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccountHubController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:users.index');
    }

    public function index(): View
    {
        $stats = [
            'users' => User::query()
                ->where(function ($q) {
                    $q->where('user_type', '!=', 'student')
                        ->orWhereNull('user_type');
                })
                ->count(),
            'students' => User::query()->where('user_type', 'student')->count(),
            'instructors' => User::query()->where('user_type', 'instructor')->count(),
            'roles' => Role::query()->count(),
            'permissions' => Permission::query()->count(),
            'active_users' => User::query()->where('status', 1)->count(),
        ];

        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return view('user::hub.index', compact('stats', 'roles'));
    }
}
