<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Đối chiếu "hồ sơ file đề đã ký" (essay_exam_approval_documents) với ngân hàng đề
 * (essay_exams): ghi lại mã đề, năm học, học kì của từng bộ đề và xóa hồ sơ ký
 * không còn bộ đề trong ngân hàng đề.
 *
 * Chạy thử (không thay đổi dữ liệu):
 *   php artisan essay-exam:audit-signed-documents --from=2026-11-17
 * Xóa thật (chỉ xóa bản ghi hồ sơ ký, PDF gốc của bộ đề vẫn giữ nguyên):
 *   php artisan essay-exam:audit-signed-documents --from=2026-11-17 --delete
 * Lưu báo cáo ra CSV để đối chiếu:
 *   php artisan essay-exam:audit-signed-documents --from=2026-11-17 --log=storage/logs/de-ky-20261126.csv
 *
 * Hồ sơ mất liên kết nhưng vẫn có bộ đề trùng tên trong ngân hàng đề thì chỉ báo,
 * không tự xóa. Trên hệ thống tách CSDL, module đề thi nằm ở kết nối "lms":
 *   php artisan essay-exam:audit-signed-documents --from=2026-11-17 --connection=lms
 */
class AuditSignedEssayDocuments extends Command
{
    protected $signature = 'essay-exam:audit-signed-documents
        {--from= : Chỉ xét hồ sơ ký từ ngày này (YYYY-MM-DD)}
        {--delete : Xóa hồ sơ ký không còn bộ đề trong ngân hàng đề}
        {--log= : Ghi báo cáo ra CSV (đường dẫn tính từ thư mục dự án)}
        {--connection= : Kết nối CSDL chứa bảng essay_exam_* (mặc định tự dò lms)}';

    protected $description = 'Ghi lại mã đề, năm học, học kì của file đề đã ký và xóa hồ sơ không còn trong ngân hàng đề';

    public function handle(): int
    {
        $connection = $this->resolveConnection();

        if ($connection === null) {
            return self::FAILURE;
        }

        $from = $this->parseFrom();

        if ($from === false) {
            return self::FAILURE;
        }

        $documents = $this->documents($connection, $from);

        if ($documents === []) {
            $this->info('Không có hồ sơ file đề đã ký nào trong khoảng lọc.');

            return self::SUCCESS;
        }

        $unlinked = array_values(array_filter($documents, fn (object $row) => $row->exam_id === null));
        $relinked = $this->matchByTitle($connection, $unlinked);
        $linkBreaks = array_filter($unlinked, fn (object $row) => isset($relinked[$row->id]));
        $orphans = array_values(array_filter($unlinked, fn (object $row) => ! isset($relinked[$row->id])));

        $this->table(
            ['#', 'Mã quyết định', 'Bộ đề đã ký', 'Ngày ký', 'Mã đề', 'Năm học', 'Học kì', 'Trạng thái'],
            array_map(fn (object $row, int $index) => [
                $index + 1,
                (string) $row->decision_code,
                $this->excerpt((string) $row->title),
                $row->approved_at ? Carbon::parse($row->approved_at)->format('d/m/Y H:i') : '—',
                $this->value($row->exam_code),
                $this->value($row->academic_year),
                $this->semesterLabel($row->semester),
                $this->status($row, $relinked[$row->id] ?? null),
            ], $documents, array_keys($documents))
        );

        $this->reportMissingMetadata($documents);
        $this->reportLinkBreaks($linkBreaks, $relinked);

        $this->newLine();
        $this->line(sprintf(
            'Tổng: %d hồ sơ · còn trong ngân hàng đề: %d · mất liên kết: %d · không còn bộ đề: %d%s',
            count($documents),
            count($documents) - count($unlinked),
            count($linkBreaks),
            count($orphans),
            $from ? ' (từ '.$from->format('d/m/Y').')' : ''
        ));

        if ($path = $this->option('log')) {
            $this->writeLog($path, $documents, $relinked);
        }

        if ($orphans === []) {
            $this->info('Không có hồ sơ nào cần xóa.');

            return self::SUCCESS;
        }

        if (! $this->option('delete')) {
            $this->warn('Chưa xóa gì. Chạy lại với --delete để xóa '.count($orphans).' hồ sơ mồ côi (PDF gốc vẫn được giữ lại).');

            return self::SUCCESS;
        }

        return $this->deleteDocuments($connection, $orphans);
    }

    /**
     * @return array<int, object>
     */
    private function documents(string $connection, ?Carbon $from): array
    {
        $hasDocSoftDeletes = Schema::connection($connection)->hasColumn('essay_exam_approval_documents', 'deleted_at');
        $hasExamSoftDeletes = Schema::connection($connection)->hasColumn('essay_exams', 'deleted_at');

        $query = DB::connection($connection)
            ->table('essay_exam_approval_documents as d')
            ->leftJoin('essay_exams as e', function ($join) use ($hasExamSoftDeletes): void {
                $join->on('e.id', '=', 'd.essay_exam_id');

                if ($hasExamSoftDeletes) {
                    $join->whereNull('e.deleted_at');
                }
            })
            ->select([
                'd.id',
                'd.essay_exam_id',
                'd.decision_code',
                'd.title',
                'd.approved_at',
                'd.class_name',
                'd.subject_name',
                'd.document_path',
                'e.id as exam_id',
                'e.code as exam_code',
                'e.academic_year',
                'e.semester',
            ])
            ->when($hasDocSoftDeletes, fn ($builder) => $builder->whereNull('d.deleted_at'))
            ->when($from !== null, fn ($builder) => $builder->where('d.approved_at', '>=', $from))
            ->orderBy('d.approved_at')
            ->orderBy('d.id');

        return $query->get()->all();
    }

    /**
     * Hồ sơ trỏ tới bộ đề đã mất nhưng ngân hàng đề vẫn còn bộ đề trùng tên:
     * coi là mất liên kết, chỉ báo để kiểm tra tay, không tự xóa.
     *
     * @param  array<int, object>  $unlinked
     * @return array<int, object>  doc id => exam
     */
    private function matchByTitle(string $connection, array $unlinked): array
    {
        if ($unlinked === []) {
            return [];
        }

        $titles = array_values(array_unique(array_map(fn (object $row) => trim((string) $row->title), $unlinked)));
        $hasExamSoftDeletes = Schema::connection($connection)->hasColumn('essay_exams', 'deleted_at');

        $found = DB::connection($connection)
            ->table('essay_exams')
            ->select(['id', 'code', 'title', 'academic_year', 'semester'])
            ->when($hasExamSoftDeletes, fn ($builder) => $builder->whereNull('deleted_at'))
            ->whereIn('title', $titles)
            ->get();

        $byTitle = [];

        foreach ($found as $exam) {
            $byTitle[$this->normalizeTitle((string) $exam->title)] ??= $exam;
        }

        $matches = [];

        foreach ($unlinked as $row) {
            $key = $this->normalizeTitle((string) $row->title);

            if (isset($byTitle[$key])) {
                $matches[(int) $row->id] = $byTitle[$key];
            }
        }

        return $matches;
    }

    private function status(object $row, ?object $relinked): string
    {
        if ($row->exam_id === null) {
            return $relinked === null
                ? 'KHÔNG CÒN — xóa'
                : 'Mất liên kết — còn bộ đề ID '.(string) $relinked->id;
        }

        $missing = $this->missingMetadata($row);

        return $missing === [] ? 'Còn' : 'Còn, thiếu '.implode(', ', $missing);
    }

    /**
     * @return array<int, string>
     */
    private function missingMetadata(object $row): array
    {
        $missing = [];

        if ($this->value($row->exam_code) === '—') {
            $missing[] = 'mã đề';
        }

        if ($this->value($row->academic_year) === '—') {
            $missing[] = 'năm học';
        }

        if ($this->value($row->semester) === '—') {
            $missing[] = 'học kì';
        }

        return $missing;
    }

    /**
     * @param  array<int, object>  $documents
     */
    private function reportMissingMetadata(array $documents): void
    {
        $rows = array_filter($documents, fn (object $row) => $row->exam_id !== null && $this->missingMetadata($row) !== []);

        if ($rows === []) {
            return;
        }

        $this->newLine();
        $this->line('Bộ đề còn trong ngân hàng đề nhưng thiếu thông tin (bổ sung trong Ngân hàng đề để màn File đề đã ký hiện đúng):');

        foreach ($rows as $row) {
            $this->line(sprintf(
                ' · ID bộ đề %s — %s — %s: thiếu %s',
                (string) $row->essay_exam_id,
                (string) $row->decision_code,
                (string) $row->title,
                implode(', ', $this->missingMetadata($row))
            ));
        }
    }

    /**
     * @param  array<int, object>  $linkBreaks
     * @param  array<int, object>  $relinked
     */
    private function reportLinkBreaks(array $linkBreaks, array $relinked): void
    {
        if ($linkBreaks === []) {
            return;
        }

        $this->newLine();
        $this->line('Hồ sơ mất liên kết nhưng ngân hàng đề vẫn còn bộ đề trùng tên (giữ lại, kiểm tra/gán lại liên kết):');

        foreach ($linkBreaks as $row) {
            $exam = $relinked[(int) $row->id];

            $this->line(sprintf(
                ' · %s — %s: trỏ tới bộ đề đã mất (ID %s) · bộ đề còn trong ngân hàng: ID %s, mã %s, %s, %s',
                (string) $row->decision_code,
                (string) $row->title,
                (string) $row->essay_exam_id,
                (string) $exam->id,
                $this->value($exam->code),
                $this->value($exam->academic_year),
                $this->semesterLabel($exam->semester)
            ));
        }
    }

    /**
     * @param  array<int, object>  $documents
     * @param  array<int, object>  $relinked
     */
    private function writeLog(string $path, array $documents, array $relinked): void
    {
        $handle = @fopen(base_path($path), 'wb');

        if ($handle === false) {
            $this->error('Không ghi được file báo cáo: '.$path);

            return;
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['ma_quyet_dinh', 'bo_de_da_ky', 'ngay_ky', 'essay_exam_id', 'ma_de', 'nam_hoc', 'hoc_ki', 'lop', 'mon', 'trang_thai']);

        foreach ($documents as $row) {
            fputcsv($handle, [
                $row->decision_code,
                $row->title,
                $row->approved_at ? Carbon::parse($row->approved_at)->format('d/m/Y H:i') : '',
                $row->essay_exam_id,
                (string) $row->exam_code,
                (string) $row->academic_year,
                (string) $row->semester,
                (string) $row->class_name,
                (string) $row->subject_name,
                $this->status($row, $relinked[$row->id] ?? null),
            ]);
        }

        fclose($handle);

        $this->info('Đã ghi báo cáo: '.$path);
    }

    /**
     * @param  array<int, object>  $orphans
     */
    private function deleteDocuments(string $connection, array $orphans): int
    {
        $ids = array_map(fn (object $row) => (int) $row->id, $orphans);
        $hasDocSoftDeletes = Schema::connection($connection)->hasColumn('essay_exam_approval_documents', 'deleted_at');

        $affected = $hasDocSoftDeletes
            ? DB::connection($connection)->table('essay_exam_approval_documents')->whereIn('id', $ids)->update([
                'deleted_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ])
            : DB::connection($connection)->table('essay_exam_approval_documents')->whereIn('id', $ids)->delete();

        $this->info(sprintf('Đã xóa %d/%d hồ sơ mồ côi. PDF gốc của bộ đề vẫn được giữ lại.', (int) $affected, count($ids)));

        return (int) $affected === count($ids) ? self::SUCCESS : self::FAILURE;
    }

    private function resolveConnection(): ?string
    {
        $explicit = $this->option('connection');
        $candidates = array_values(array_unique(array_filter([$explicit, 'lms', config('database.default')])));

        foreach ($candidates as $name) {
            try {
                if (Schema::connection($name)->hasTable('essay_exam_approval_documents')) {
                    $this->line('Kết nối CSDL: '.$name);

                    return $name;
                }
            } catch (Throwable) {
                continue;
            }
        }

        $this->error('Không tìm thấy bảng essay_exam_approval_documents trong các kết nối: '.implode(', ', $candidates).'. Dùng --connection=<tên kết nối>.');

        return null;
    }

    /**
     * @return Carbon|false|null
     */
    private function parseFrom(): Carbon|false|null
    {
        $from = $this->option('from');

        if ($from === null || $from === '') {
            return null;
        }

        try {
            return Carbon::parse($from)->startOfDay();
        } catch (Throwable) {
            $this->error('Ngày không hợp lệ: '.$from.' (định dạng YYYY-MM-DD).');

            return false;
        }
    }

    private function value(?string $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? '—' : $value;
    }

    private function semesterLabel(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '—';
        }

        if (mb_strtolower($value) === 'summer') {
            return 'Hè';
        }

        return str_replace('semester_', 'HK ', $value);
    }

    private function normalizeTitle(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)) ?? $value);
    }

    private function excerpt(string $value, int $limit = 42): string
    {
        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit - 1).'…' : $value;
    }
}
