<?php

namespace Modules\Lms\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsScormAttempt;
use Modules\Lms\Models\LmsScormPackage;

/**
 * Sprint 9 T3 — SCORM 1.2-style runtime commit (completion + score + suspend).
 */
class LmsScormService
{
    public function __construct(protected LmsProgressService $progress) {}

    public function getOrStart(LmsCourse $course, LmsScormPackage $package, ?User $user = null): LmsScormAttempt
    {
        $user = $user ?: Auth::user();

        return LmsScormAttempt::query()->updateOrCreate(
            [
                'lms_scorm_package_id' => $package->id,
                'user_id' => $user->id,
            ],
            [
                'lms_course_id' => $course->id,
                'started_at' => now(),
                'lesson_status' => 'incomplete',
            ]
        );
    }

    /**
     * @param  array<string,mixed>  $cmi  flat map cmi.core.* keys
     */
    public function commit(LmsCourse $course, LmsScormPackage $package, array $cmi, ?User $user = null): LmsScormAttempt
    {
        $user = $user ?: Auth::user();
        $attempt = $this->getOrStart($course, $package, $user);

        $lessonStatus = $cmi['cmi.core.lesson_status'] ?? $cmi['lesson_status'] ?? $attempt->lesson_status;
        $success = $cmi['cmi.success_status'] ?? $cmi['success_status'] ?? $attempt->success_status;
        $scoreRaw = $cmi['cmi.core.score.raw'] ?? $cmi['score_raw'] ?? $attempt->score_raw;
        $scoreMax = $cmi['cmi.core.score.max'] ?? $cmi['score_max'] ?? $attempt->score_max;
        $scoreMin = $cmi['cmi.core.score.min'] ?? $cmi['score_min'] ?? $attempt->score_min;
        $suspend = $cmi['cmi.suspend_data'] ?? $cmi['suspend_data'] ?? $attempt->suspend_data;
        $location = $cmi['cmi.core.lesson_location'] ?? $cmi['lesson_location'] ?? $attempt->lesson_location;

        $sessionSec = $this->parseCmTime($cmi['cmi.core.session_time'] ?? $cmi['session_time'] ?? null);
        $totalSec = (int) $attempt->total_time_sec + $sessionSec;

        $merged = array_merge($attempt->cmi_data ?? [], $cmi);

        $complete = in_array(strtolower((string) $lessonStatus), ['completed', 'passed'], true)
            || strtolower((string) $success) === 'passed';

        $attempt->fill([
            'lesson_status' => $lessonStatus,
            'success_status' => $success,
            'score_raw' => $scoreRaw !== null && $scoreRaw !== '' ? (float) $scoreRaw : null,
            'score_max' => $scoreMax !== null && $scoreMax !== '' ? (float) $scoreMax : null,
            'score_min' => $scoreMin !== null && $scoreMin !== '' ? (float) $scoreMin : null,
            'score_scaled' => $this->scaled($scoreRaw, $scoreMax),
            'session_time_sec' => $sessionSec,
            'total_time_sec' => $totalSec,
            'suspend_data' => is_string($suspend) ? $suspend : $attempt->suspend_data,
            'lesson_location' => is_string($location) ? $location : $attempt->lesson_location,
            'cmi_data' => $merged,
            'last_commit_at' => now(),
            'completed_at' => $complete ? ($attempt->completed_at ?? now()) : $attempt->completed_at,
        ])->save();

        $pct = $complete ? 100 : max(10, min(99, (int) round(($attempt->score_scaled ?? 0) * 100)));
        try {
            $this->progress->record(
                $course,
                'scorm',
                $package->id,
                $complete ? 'complete' : 'view',
                $pct,
                [
                    'lesson_status' => $attempt->lesson_status,
                    'score_raw' => $attempt->score_raw,
                    'score_max' => $attempt->score_max,
                ],
                $user
            );
        } catch (\Throwable $e) {
        }

        return $attempt->fresh();
    }

    protected function scaled(mixed $raw, mixed $max): ?float
    {
        if ($raw === null || $raw === '' || $max === null || $max === '' || (float) $max <= 0) {
            return null;
        }

        return round(((float) $raw) / (float) $max, 4);
    }

    /** Parse SCORM 1.2 time HHHH:MM:SS.ss → seconds */
    protected function parseCmTime(mixed $t): int
    {
        if ($t === null || $t === '') {
            return 0;
        }
        if (is_numeric($t)) {
            return max(0, (int) $t);
        }
        $t = (string) $t;
        if (preg_match('/^(\d+):(\d{2}):(\d{2})(?:\.(\d+))?$/', $t, $m)) {
            return ((int) $m[1]) * 3600 + ((int) $m[2]) * 60 + (int) $m[3];
        }

        return 0;
    }
}
