<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-training-schedule', function() {
    try {
        // Check data availability
        $specializations = \Modules\Specialization\Models\Specialization::count();
        $subjects = \Modules\Subject\Models\Subject::count();
        $instructors = \App\Models\User::count(); // Đếm tất cả users thay vì theo role
        $classrooms = \Modules\Classroom\Models\Classroom::count();
        $classes = \Modules\Class\Models\ClassModel::count();
        
        return response()->json([
            'success' => true,
            'data_counts' => [
                'specializations' => $specializations,
                'subjects' => $subjects,
                'instructors' => $instructors,
                'classrooms' => $classrooms,
                'classes' => $classes,
            ],
            'routes' => [
                'create' => route('training-schedules.create'),
                'store' => route('training-schedules.store'),
            ],
            'user' => auth()->user() ? auth()->user()->name : 'Not authenticated'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine()
        ]);
    }
});

Route::post('/debug-training-schedule-store', function(\Illuminate\Http\Request $request) {
    try {
        $data = $request->all();
        
        return response()->json([
            'success' => true,
            'message' => 'Debug POST successful',
            'received_data' => $data,
            'validation_errors' => [],
            'user' => auth()->user() ? auth()->user()->name : 'Not authenticated'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine()
        ]);
    }
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
