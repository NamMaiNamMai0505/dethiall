<?php

namespace Modules\Grades\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Class\Models\ClassModel;
use Modules\Grades\Models\ConductRecord;
use Modules\Grades\Models\GraduationResult;
use Modules\Grades\Models\GraduationSession;
use Modules\Grades\Support\GradeSettings;

/**
 * Tính kết quả tổng kết / xét TN từ bảng điểm + rèn luyện.
 */
class GraduationCalculator
{
    /**
     * @return array{count:int, with_score:int}
     */
    public function calculate(GraduationSession $session, User $actor): array
    {
        $classIds = ClassModel::query()->where('is_active', true)->pluck('id');
        $students = User::query()
            ->where('user_type', 'student')
            ->whereIn('class_id', $classIds)
            ->orderBy('name')
            ->get(['id', 'class_id', 'name', 'code']);

        $maxScore = GradeSettings::maxScore();
        $thresholds = config('grades.result_thresholds', ['gioi' => $maxScore * 0.8, 'tot_nghiep' => 5.0]);
        $thresholds['gioi'] = GradeSettings::excellentScore();
        $thresholds['tot_nghiep'] = GradeSettings::passScore();
        $weights = GradeSettings::columnWeights();

        $scoresByUser = $this->loadSubjectAverages($weights);
        $conductByUser = $this->loadConductMap($session->academic_year);

        $ranked = [];
        foreach ($students as $stu) {
            $gpa = $scoresByUser[$stu->id]['gpa'] ?? null;
            $exam = $scoresByUser[$stu->id]['final'] ?? null;
            $status = $this->decideStatus($gpa, $exam, $conductByUser[$stu->id] ?? null, $thresholds);

            $ranked[] = [
                'user_id' => $stu->id,
                'class_id' => $stu->class_id,
                'gpa' => $gpa,
                'exam_score' => $exam,
                'result_status' => $status,
                'sort_key' => $gpa ?? -1,
            ];
        }

        // Xếp hạng theo GPA giảm dần (null cuối)
        usort($ranked, function ($a, $b) {
            if ($a['sort_key'] === $b['sort_key']) {
                return 0;
            }

            return $a['sort_key'] < $b['sort_key'] ? 1 : -1;
        });

        $withScore = 0;
        $rank = 1;
        DB::transaction(function () use ($session, $ranked, $actor, &$withScore, &$rank) {
            foreach ($ranked as $row) {
                if ($row['gpa'] !== null) {
                    $withScore++;
                }
                GraduationResult::query()->updateOrCreate(
                    ['session_id' => $session->id, 'user_id' => $row['user_id']],
                    [
                        'class_id' => $row['class_id'],
                        'rank_order' => $rank++,
                        'result_status' => $row['result_status'],
                        'gpa' => $row['gpa'],
                        'exam_score' => $row['exam_score'],
                        'updated_by' => $actor->id,
                    ]
                );
            }
            $session->update(['status' => GraduationSession::STATUS_CALCULATED]);
        });

        return ['count' => count($ranked), 'with_score' => $withScore];
    }

    /**
     * TB môn theo trọng số cột; final riêng cho điểm thi TN.
     *
     * @return array<int, array{gpa:?float, final:?float}>
     */
    protected function loadSubjectAverages(array $weights): array
    {
        $out = [];
        if (! Schema::hasTable('grade_cells') || ! Schema::hasTable('grade_columns') || ! Schema::hasTable('grade_books')) {
            return $out;
        }

        $rows = DB::table('grade_cells as gc')
            ->join('grade_columns as col', 'col.id', '=', 'gc.grade_column_id')
            ->join('grade_books as gb', 'gb.id', '=', 'gc.grade_book_id')
            ->whereNotNull('gc.score')
            ->select('gc.user_id', 'gb.subject_id', 'col.code', 'gc.score')
            ->get();

        // user → subject → code → score (lấy điểm mới nhất gần đúng: trung bình nếu nhiều bảng)
        $bucket = [];
        foreach ($rows as $r) {
            $uid = (int) $r->user_id;
            $sid = (int) $r->subject_id;
            $code = (string) $r->code;
            $bucket[$uid][$sid][$code][] = (float) $r->score;
        }

        foreach ($bucket as $uid => $subjects) {
            $subjectGpas = [];
            $finals = [];
            foreach ($subjects as $sid => $codes) {
                $avgCodes = [];
                foreach ($codes as $code => $scores) {
                    $avgCodes[$code] = array_sum($scores) / max(count($scores), 1);
                }
                $subjectGpas[] = $this->weightedAverage($avgCodes, $weights);
                if (isset($avgCodes['final'])) {
                    $finals[] = $avgCodes['final'];
                }
            }
            $valid = array_values(array_filter($subjectGpas, fn ($v) => $v !== null));
            $out[$uid] = [
                'gpa' => $valid === [] ? null : GradeSettings::round(array_sum($valid) / count($valid)),
                'final' => $finals === [] ? null : GradeSettings::round(array_sum($finals) / count($finals)),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, float>  $scoresByCode
     */
    protected function weightedAverage(array $scoresByCode, array $weights): ?float
    {
        if ($scoresByCode === []) {
            return null;
        }
        $num = 0.0;
        $den = 0.0;
        foreach ($scoresByCode as $code => $score) {
            $w = (float) ($weights[$code] ?? 0);
            if ($w <= 0) {
                $w = 1.0 / max(count($scoresByCode), 1);
            }
            $num += $score * $w;
            $den += $w;
        }
        if ($den <= 0) {
            return null;
        }

        return GradeSettings::round($num / $den);
    }

    /**
     * @return array<int, ConductRecord|object>
     */
    protected function loadConductMap(?string $year): array
    {
        if (! Schema::hasTable('grade_conduct_records')) {
            return [];
        }
        $q = ConductRecord::query();
        if ($year) {
            $q->where('academic_year', $year);
        }

        return $q->get()->keyBy('user_id')->all();
    }

    protected function decideStatus(?float $gpa, ?float $exam, $conduct, array $thresholds): string
    {
        if ($conduct && ! empty($conduct->suspended)) {
            return 'thoi_hoc';
        }
        if ($conduct && ! empty($conduct->discipline)) {
            $d = mb_strtolower((string) $conduct->discipline);
            if (str_contains($d, 'đình chỉ') || str_contains($d, 'dinh chi')) {
                return 'dinh_chi';
            }
        }

        // Ưu tiên điểm thi TN nếu có
        $score = $exam ?? $gpa;
        if ($score === null) {
            return 'pending';
        }

        $gioi = (float) ($thresholds['gioi'] ?? 8);
        $pass = (float) ($thresholds['tot_nghiep'] ?? 5);

        if ($score >= $gioi) {
            return 'gioi';
        }
        if ($score >= $pass) {
            return 'tot_nghiep';
        }

        return 'khong_tn';
    }
}
