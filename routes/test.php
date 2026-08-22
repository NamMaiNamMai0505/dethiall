<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Test route to check current user permissions
Route::get('/test-permissions', function () {
    if (!Auth::check()) {
        return response()->json(['error' => 'Not authenticated']);
    }
    
    $user = Auth::user();
    $permissions = [];
    
    // Check all user module permissions
    $userPermissions = ['users.index', 'users.show', 'users.create', 'users.edit', 'users.delete'];
    
    foreach ($userPermissions as $permission) {
        $permissions[$permission] = $user->can($permission) ? 'YES' : 'NO';
    }
    
    return response()->json([
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ],
        'roles' => $user->getRoleNames(),
        'permissions' => $permissions,
        'all_permissions_count' => $user->getAllPermissions()->count(),
    ]);
})->middleware(['web', 'auth']);

// Test route to auto-login as admin user
Route::get('/test-login-admin', function () {
    $user = \App\Models\User::where('email', 'admin@example.com')->first();
    if ($user) {
        Auth::login($user);
        return redirect('/users')->with('success', 'Đã đăng nhập như admin@example.com');
    }
    return 'Admin user not found';
})->middleware('web');