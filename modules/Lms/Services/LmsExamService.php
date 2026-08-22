<?php

namespace Modules\Lms\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsExam;
use Modules\Lms\Models\LmsExamAttempt;
use Modules\Lms\Models\LmsQuestion;

class LmsExamService
{
    public function startAttempt(LmsExam $exam): LmsExamAttempt
    {
        $userId = Auth::id();
        $done = LmsExamAttempt::query()
            ->where('lms_exam_id', $exam->id)
            ->where('user_id', $userId)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        if ($done >= (int) $exam->max_attempts) {
            throw new \RuntimeException('Đã hết số lần làm bài cho phép.');
        }

        $open = LmsExamAttempt::query()
            ->where('lms_exam_id', $exam->id)
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->first();
        if ($open) {
            return $open;
        }

        if (! $exam->isOpenNow()) {
            throw new \RuntimeException('Bài thi chưa mở hoặc đã đóng.');
        }

        $exam->load('questions');
        $qids = $exam->questions->pluck('id')->all();
        if ($exam->shuffle_questions) {
            shuffle($qids);
        }

        return LmsExamAttempt::create([
            'lms_exam_id' => $exam->id,
            'user_id' => $userId,
            'started_at' => now(),
            'status' => 'in_progress',
            'question_order' => $qids,
            'answers' => [],
            'proctor_events' => [],
            'blur_count' => 0,
        ]);
    }

    public function submitAttempt(LmsExamAttempt $attempt, array $answers, array $proctorEvents = []): LmsExamAttempt
    {
        $exam = $attempt->exam()->with('questions')->first();
        $score = 0.0;
        $max = 0.0;

        foreach ($exam->questions as $q) {
            /** @var LmsQuestion $q */
            $pts = (float) ($q->pivot->points ?? $q->points ?? 1);
            $max += $pts;
            $ans = $answers[(string) $q->id] ?? $answers[$q->id] ?? null;
            if ($q->isCorrect($ans !== null ? (string) $ans : null)) {
                $score += $pts;
            }
        }

        $events = array_merge($attempt->proctor_events ?? [], $proctorEvents);
        $blur = (int) $attempt->blur_count;
        foreach ($proctorEvents as $ev) {
            $type = (string) ($ev['type'] ?? '');
            if (in_array($type, ['blur', 'visibility', 'window_blur', 'exit_fullscreen', 'tab_hidden'], true)) {
                $blur++;
            }
        }

        $attempt->update([
            'answers' => $answers,
            'submitted_at' => now(),
            'status' => 'graded',
            'score' => round($score, 2),
            'max_score' => round($max, 2),
            'proctor_events' => $events,
            'blur_count' => $blur,
        ]);

        return $attempt->fresh();
    }

    /** Sprint 9 T4 — heartbeat proctor events khi đang làm (không webcam). */
    public function appendProctorEvents(LmsExamAttempt $attempt, array $proctorEvents): LmsExamAttempt
    {
        if ($attempt->status !== 'in_progress' || $proctorEvents === []) {
            return $attempt;
        }
        $events = array_merge($attempt->proctor_events ?? [], $proctorEvents);
        // cap storage
        if (count($events) > 200) {
            $events = array_slice($events, -200);
        }
        $blur = (int) $attempt->blur_count;
        foreach ($proctorEvents as $ev) {
            $type = (string) ($ev['type'] ?? '');
            if (in_array($type, ['blur', 'visibility', 'window_blur', 'exit_fullscreen', 'tab_hidden'], true)) {
                $blur++;
            }
        }
        $attempt->update([
            'proctor_events' => $events,
            'blur_count' => $blur,
        ]);

        return $attempt->fresh();
    }
}
