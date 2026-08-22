<?php

namespace Modules\Lms\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCertificate;
use Modules\Lms\Models\LmsCertificateTemplate;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsGradebookRow;
use Modules\Lms\Models\LmsProgressSummary;
use Modules\Lms\Models\LmsSurvey;
use Modules\Lms\Models\LmsSurveyResponse;

class LmsCertificateService
{
    public function __construct(protected LmsGradebookService $gradebook) {}

    public function evaluateEligibility(LmsCourse $course, User $user, ?LmsCertificateTemplate $template = null): array
    {
        $template ??= LmsCertificateTemplate::query()
            ->where('lms_course_id', $course->id)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        $minProgress = (float) ($template?->min_progress_pct ?? 80);
        $minScore = $template?->min_score;

        $progress = LmsProgressSummary::query()
            ->where('lms_course_id', $course->id)
            ->where('user_id', $user->id)
            ->value('overall_pct');
        $progress = (float) ($progress ?? 0);

        $row = LmsGradebookRow::query()
            ->where('lms_course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();
        if (! $row) {
            $matrix = $this->gradebook->matrix($course, $user->id);
            $data = $matrix['rows'][$user->id] ?? null;
            $score = $data['final_score'] ?? $data['computed_score'] ?? null;
        } else {
            $score = $row->final_score ?? $row->computed_score;
        }

        $surveyOk = true;
        if ($template?->require_survey) {
            $surveyIds = LmsSurvey::query()
                ->where('lms_course_id', $course->id)
                ->where('is_published', true)
                ->pluck('id');
            if ($surveyIds->isNotEmpty()) {
                $answered = LmsSurveyResponse::query()
                    ->whereIn('lms_survey_id', $surveyIds)
                    ->where('user_id', $user->id)
                    ->exists();
                $surveyOk = $answered;
            }
        }

        $reasons = [];
        if ($progress < $minProgress) {
            $reasons[] = "Tiến độ {$progress}% < {$minProgress}%";
        }
        if ($minScore !== null && ($score === null || (float) $score < (float) $minScore)) {
            $reasons[] = 'Điểm tổng hợp chưa đạt '.((float) $minScore);
        }
        if (! $surveyOk) {
            $reasons[] = 'Chưa hoàn thành khảo sát chất lượng';
        }

        return [
            'eligible' => empty($reasons),
            'reasons' => $reasons,
            'progress_pct' => $progress,
            'final_score' => $score,
            'template' => $template,
        ];
    }

    /**
     * Đánh giá nhiều học viên bằng một cụm truy vấn, tránh N+1 trên bảng lớp.
     *
     * @param  Collection<int,User>  $users
     * @return array<int,array{eligible:bool,reasons:array,progress_pct:float,final_score:mixed,template:mixed}>
     */
    public function evaluateMany(LmsCourse $course, Collection $users): array
    {
        if ($users->isEmpty()) {
            return [];
        }

        $template = LmsCertificateTemplate::query()
            ->where('lms_course_id', $course->id)
            ->where('is_active', true)
            ->latest('id')
            ->first();
        $userIds = $users->pluck('id');
        $progress = LmsProgressSummary::query()
            ->where('lms_course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->pluck('overall_pct', 'user_id');
        $gradeRows = LmsGradebookRow::query()
            ->where('lms_course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');
        $matrix = $gradeRows->count() < $users->count() ? $this->gradebook->matrix($course) : ['rows' => []];

        $surveyIds = collect();
        $answered = collect();
        if ($template?->require_survey) {
            $surveyIds = LmsSurvey::query()
                ->where('lms_course_id', $course->id)
                ->where('is_published', true)
                ->pluck('id');
            if ($surveyIds->isNotEmpty()) {
                $answered = LmsSurveyResponse::query()
                    ->whereIn('lms_survey_id', $surveyIds)
                    ->whereIn('user_id', $userIds)
                    ->pluck('user_id')
                    ->unique();
            }
        }

        $results = [];
        foreach ($users as $user) {
            $progressPct = (float) ($progress[$user->id] ?? 0);
            $row = $gradeRows->get($user->id);
            $matrixRow = $matrix['rows'][$user->id] ?? null;
            $score = $row?->displayScore() ?? ($matrixRow['final_score'] ?? $matrixRow['computed_score'] ?? null);
            $reasons = [];
            $minProgress = (float) ($template?->min_progress_pct ?? 80);
            if ($progressPct < $minProgress) {
                $reasons[] = "Tiến độ {$progressPct}% < {$minProgress}%";
            }
            if ($template?->min_score !== null && ($score === null || (float) $score < (float) $template->min_score)) {
                $reasons[] = 'Điểm tổng hợp chưa đạt '.((float) $template->min_score);
            }
            if ($surveyIds->isNotEmpty() && ! $answered->contains($user->id)) {
                $reasons[] = 'Chưa hoàn thành khảo sát chất lượng';
            }
            $results[$user->id] = [
                'eligible' => $reasons === [],
                'reasons' => $reasons,
                'progress_pct' => $progressPct,
                'final_score' => $score,
                'template' => $template,
            ];
        }

        return $results;
    }

    public function issue(
        LmsCourse $course,
        User $user,
        ?LmsCertificateTemplate $template = null,
        bool $force = false,
        ?array $evaluation = null,
    ): LmsCertificate {
        $check = $evaluation ?? $this->evaluateEligibility($course, $user, $template);
        if (! $force && ! $check['eligible']) {
            throw new \RuntimeException('Chưa đủ điều kiện cấp chứng chỉ: '.implode('; ', $check['reasons']));
        }

        $template = $check['template'] ?? $template;
        $title = $template?->title ?: ('Chứng nhận hoàn thành: '.$course->title);

        return LmsCertificate::query()->updateOrCreate(
            ['lms_course_id' => $course->id, 'user_id' => $user->id],
            [
                'lms_certificate_template_id' => $template?->id,
                'code' => LmsCertificate::makeCode(),
                'title' => $title,
                'final_score' => $check['final_score'],
                'progress_pct' => $check['progress_pct'],
                'issued_at' => now(),
                'issued_by' => Auth::id(),
                'status' => 'issued',
                'meta' => [
                    'course_title' => $course->title,
                    'issuer' => $template?->issuer_name ?? config('app.name'),
                ],
            ]
        );
    }

    public function issueEligible(LmsCourse $course): int
    {
        $studentIds = $course->members()->where('role', 'student')->pluck('user_id');
        $users = User::query()->whereIn('id', $studentIds)->get();
        $checks = $this->evaluateMany($course, $users);
        $n = 0;
        foreach ($users as $user) {
            $check = $checks[$user->id];
            if ($check['eligible']) {
                $this->issue($course, $user, $check['template'], false, $check);
                $n++;
            }
        }

        return $n;
    }
}
