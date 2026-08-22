<?php

namespace Modules\Grades\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Class\Models\ClassModel;
use Modules\Grades\Models\GraduationProfile;
use Modules\Grades\Models\GraduationSession;
use Modules\Grades\Services\GradeAccess;
use Modules\Grades\Services\GradeActor;

class GraduationProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);

        $profiles = GraduationProfile::query()
            ->with(['classModel', 'session'])
            ->orderByDesc('id')
            ->paginate(20);
        $canEdit = GradeActor::canEditOperational($user);
        $canApprove = GradeActor::canApprove($user);
        $sessions = GraduationSession::query()->orderByDesc('id')->limit(50)->get(['id', 'title', 'academic_year']);
        $classes = ClassModel::query()->orderBy('name')->pluck('name', 'id');

        return view('grades::academic.profiles.index', compact(
            'profiles', 'canEdit', 'canApprove', 'sessions', 'classes'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEditOperational($user) || GradeActor::canFull($user), 403);

        $data = $request->validate([
            'session_id' => 'nullable|exists:grade_graduation_sessions,id',
            'class_id' => 'nullable|exists:classes,id',
            'major_name' => 'nullable|string|max:255',
            'cohort' => 'nullable|string|max:100',
            'system_type' => 'nullable|string|max:100',
            'duration_text' => 'nullable|string|max:100',
            'decision_number' => 'nullable|string|max:100',
            'decision_date' => 'nullable|date',
            'diploma_sign_date' => 'nullable|date',
            'registry_number' => 'nullable|string|max:100',
            'series_number' => 'nullable|string|max:100',
            'series_group' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:2000',
        ]);

        $profile = GraduationProfile::query()->create([
            ...$data,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('grades.academic.profiles.edit', $profile)
            ->with('success', 'Đã tạo hồ sơ tốt nghiệp.');
    }

    public function edit(GraduationProfile $profile)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);

        $canEdit = GradeActor::canEditOperational($user);
        $canApprove = GradeActor::canApprove($user);
        $sessions = GraduationSession::query()->orderByDesc('id')->limit(50)->get(['id', 'title', 'academic_year']);
        $classes = ClassModel::query()->orderBy('name')->pluck('name', 'id');

        return view('grades::academic.profiles.edit', compact(
            'profile', 'canEdit', 'canApprove', 'sessions', 'classes'
        ));
    }

    public function update(Request $request, GraduationProfile $profile)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEditOperational($user) || GradeActor::canFull($user), 403);

        $data = $request->validate([
            'session_id' => 'nullable|exists:grade_graduation_sessions,id',
            'class_id' => 'nullable|exists:classes,id',
            'major_name' => 'nullable|string|max:255',
            'cohort' => 'nullable|string|max:100',
            'system_type' => 'nullable|string|max:100',
            'duration_text' => 'nullable|string|max:100',
            'decision_number' => 'nullable|string|max:100',
            'decision_date' => 'nullable|date',
            'diploma_sign_date' => 'nullable|date',
            'registry_number' => 'nullable|string|max:100',
            'series_number' => 'nullable|string|max:100',
            'series_group' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:2000',
        ]);

        $profile->update($data);

        return back()->with('success', 'Đã ghi dữ liệu hồ sơ.');
    }

    public function submitApprove(GraduationProfile $profile)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEditOperational($user) || GradeActor::canFull($user), 403);
        $profile->update(['status' => 'pending_approve']);

        return back()->with('success', 'Đã gửi BGH phê duyệt hồ sơ.');
    }

    public function approve(GraduationProfile $profile)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canApprove($user) || GradeActor::canFull($user), 403);
        $profile->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Đã phê duyệt hồ sơ tốt nghiệp.');
    }

    public function printScore(GraduationProfile $profile)
    {
        abort_unless(GradeActor::canPrint(Auth::user()), 403);
        $profile->load(['classModel', 'session']);

        return view('grades::academic.profiles.print-score', compact('profile'));
    }

    public function printDiploma(GraduationProfile $profile)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canPrintDiploma($user) || GradeActor::canFull($user), 403);
        abort_unless(
            GradeActor::canFull($user)
            || GradeActor::canApprove($user)
            || GradeActor::canEditOperational($user),
            403
        );
        $profile->load(['classModel', 'session']);

        return view('grades::academic.profiles.print-diploma', compact('profile'));
    }
}
