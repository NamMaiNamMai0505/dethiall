<?php

namespace Modules\Lms\Controllers\Learn;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;

/**
 * Portal học viên / giảng viên — layout riêng, không dính admin dashboard.
 */
class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:lms.index']);
    }

    public function index(LmsCourseService $service)
    {
        $user = Auth::user();

        // GV mặc định vào portal dạy (trừ khi admin preview cố tình vào /lms/hoc)
        if (LmsAccess::isInstructorUser($user) && ! LmsAccess::usesAdminShell($user)) {
            return redirect()->route('lms.teach.home');
        }

        $courses = $service->queryForUser($user)
            ->withCount(['lessons', 'materials', 'members'])
            ->orderByDesc('id')
            ->paginate(12);

        return view('lms::learn.home', [
            'user' => $user,
            'courses' => $courses,
            'isAdminPreview' => LmsAccess::usesAdminShell($user),
        ]);
    }
}
