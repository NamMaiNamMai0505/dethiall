<?php

namespace Modules\Specialization\Controllers;

use App\Http\Controllers\Controller;
use App\Support\TrainingDept;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Specialization\Models\Specialization;
use Modules\Specialization\Models\TrainingSystem;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;

/**
 * Hub Ngành đào tạo — gom bốn cấp của chương trình đào tạo về một chỗ:
 * Hệ đào tạo → Ngành đào tạo → Môn học → Bài học.
 */
class CurriculumHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:specializations.index']);
    }

    public function index(): View
    {
        $user = Auth::user();

        $subjectQuery = Subject::query()->active();
        TrainingDept::applySubjectFacultyScope($subjectQuery);
        $subjectIds = $subjectQuery->pluck('id');

        $stats = [
            'training_systems' => TrainingSystem::query()->active()->count(),
            'specializations' => Specialization::query()->active()->count(),
            'subjects' => $subjectIds->count(),
            'lessons' => SubjectLesson::query()->whereIn('subject_id', $subjectIds)->count(),
        ];

        $menuItems = [
            [
                'route' => 'training-systems.index',
                'icon' => 'bi-layers',
                'label' => 'Hệ đào tạo',
                'desc' => 'Dân sự / Quân sự — cấp phân loại cao nhất của chương trình',
                'perm' => 'specializations.index',
                'iconBg' => 'bg-teal-100 text-teal-700',
                'count' => $stats['training_systems'],
            ],
            [
                'route' => 'specializations.index',
                'icon' => 'bi-mortarboard',
                'label' => 'Ngành đào tạo',
                'desc' => 'Danh mục ngành theo Mã số — trình độ, thời gian, hình thức đào tạo',
                'perm' => 'specializations.index',
                'iconBg' => 'bg-blue-100 text-blue-700',
                'count' => $stats['specializations'],
                'primary' => true,
            ],
            [
                'route' => 'subjects.index',
                'icon' => 'bi-journal-text',
                'label' => 'Môn học',
                'desc' => 'Môn thuộc từng ngành — tín chỉ, số tiết, viết tắt, màu nhận diện',
                'perm' => 'subjects.index',
                'iconBg' => 'bg-purple-100 text-purple-700',
                'count' => $stats['subjects'],
            ],
            [
                'route' => 'subject-lessons.index',
                'icon' => 'bi-list-nested',
                'label' => 'Bài học',
                'desc' => 'Khung chương trình chi tiết của từng môn — bài, unit, số giờ',
                'perm' => 'subject-lessons.index',
                'iconBg' => 'bg-amber-100 text-amber-800',
                'count' => $stats['lessons'],
            ],
        ];

        $visibleMenu = collect($menuItems)
            ->filter(fn (array $item) => $user?->can($item['perm']))
            ->values()
            ->all();

        return view('specialization::hub.index', [
            'stats' => $stats,
            'menuItems' => $visibleMenu,
            'scopeLabel' => TrainingDept::scopeLabel($user),
            'user' => $user,
        ]);
    }
}
