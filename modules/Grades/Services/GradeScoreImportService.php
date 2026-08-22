<?php

namespace Modules\Grades\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Grades\Models\GradeAuditLog;
use Modules\Grades\Models\GradeBook;
use Modules\Grades\Models\GradeCell;
use Modules\Grades\Models\GradeColumn;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import điểm từ xlsx/xls/csv vào grade_book.
 * Cột: Mã HV / Họ tên + 15 phút / 1 tiết / Giữa kỳ / Điểm thi
 */
class GradeScoreImportService
{
    /**
     * @return array{imported:int, skipped:int, unmatched:list<string>, book:GradeBook}
     */
    public function import(GradeBook $book, UploadedFile $file, User $actor): array
    {
        $book->loadMissing('columns');
        $path = $file->getRealPath() ?: $file->getPathname();
        $rows = $this->readRows($path, strtolower($file->getClientOriginalExtension() ?: ''));

        if ($rows === []) {
            throw new \RuntimeException('File rỗng hoặc không đọc được.');
        }

        $headerMap = $this->mapHeader(array_shift($rows));
        if (! isset($headerMap['student_code']) && ! isset($headerMap['student_name'])) {
            throw new \RuntimeException('Thiếu cột nhận diện học viên (Mã HV hoặc Họ tên).');
        }

        $students = User::query()
            ->where('user_type', 'student')
            ->where('class_id', $book->class_id)
            ->get(['id', 'name', 'code']);

        $byCode = $students->keyBy(fn ($s) => mb_strtolower(trim((string) $s->code)));
        $byName = $students->keyBy(fn ($s) => mb_strtolower(trim((string) $s->name)));

        $columnsByCode = $book->columns->keyBy('code');
        $imported = 0;
        $skipped = 0;
        $unmatched = [];

        DB::transaction(function () use (
            $rows, $headerMap, $byCode, $byName, $columnsByCode, $book, $actor,
            &$imported, &$skipped, &$unmatched
        ) {
            foreach ($rows as $i => $row) {
                if ($this->rowEmpty($row)) {
                    continue;
                }

                $code = isset($headerMap['student_code'])
                    ? mb_strtolower(trim((string) ($row[$headerMap['student_code']] ?? '')))
                    : '';
                $name = isset($headerMap['student_name'])
                    ? mb_strtolower(trim((string) ($row[$headerMap['student_name']] ?? '')))
                    : '';

                $student = null;
                if ($code !== '' && $byCode->has($code)) {
                    $student = $byCode->get($code);
                } elseif ($name !== '' && $byName->has($name)) {
                    $student = $byName->get($name);
                }

                if (! $student) {
                    $skipped++;
                    $unmatched[] = 'Dòng '.($i + 2).': '.($code ?: $name ?: '(trống)');

                    continue;
                }

                $wrote = false;
                foreach (['oral_15', 'period_1', 'midterm', 'final'] as $colCode) {
                    if (! isset($headerMap[$colCode])) {
                        continue;
                    }
                    $column = $columnsByCode->get($colCode);
                    if (! $column) {
                        continue;
                    }
                    if (! GradeAccess::canEditCell($actor, $book, $column)) {
                        continue;
                    }

                    $raw = $row[$headerMap[$colCode]] ?? null;
                    if ($raw === null || $raw === '') {
                        continue;
                    }
                    if (! is_numeric(str_replace(',', '.', (string) $raw))) {
                        continue;
                    }
                    $score = (float) str_replace(',', '.', (string) $raw);

                    GradeCell::query()->updateOrCreate(
                        [
                            'grade_column_id' => $column->id,
                            'user_id' => $student->id,
                        ],
                        [
                            'grade_book_id' => $book->id,
                            'score' => $score,
                            'updated_by' => $actor->id,
                        ]
                    );
                    $wrote = true;
                }

                if ($wrote) {
                    $imported++;
                } else {
                    $skipped++;
                }
            }

            GradeAuditLog::record($book->id, 'scores_imported', [
                'imported' => $imported,
                'skipped' => $skipped,
            ]);
        });

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'unmatched' => array_slice($unmatched, 0, 20),
            'book' => $book->fresh(['columns']),
        ];
    }

    /**
     * @return list<list<mixed>>
     */
    protected function readRows(string $path, string $ext): array
    {
        if (in_array($ext, ['csv', 'txt'], true)) {
            $rows = [];
            if (($h = fopen($path, 'r')) === false) {
                return [];
            }
            while (($data = fgetcsv($h)) !== false) {
                $rows[] = $data;
            }
            fclose($h);

            return $rows;
        }

        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = [];
        foreach ($sheet->toArray(null, true, true, false) as $row) {
            $rows[] = array_values($row);
        }

        return $rows;
    }

    /**
     * @param  list<mixed>  $header
     * @return array<string, int> field => column index
     */
    protected function mapHeader(array $header): array
    {
        $hints = config('grades.import_headers', []);
        $map = [];
        foreach ($header as $idx => $cell) {
            $key = $this->normalizeHeader((string) $cell);
            if ($key === '' || ! isset($hints[$key])) {
                continue;
            }
            $field = $hints[$key];
            if (! isset($map[$field])) {
                $map[$field] = (int) $idx;
            }
        }

        return $map;
    }

    protected function normalizeHeader(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;

        return $v;
    }

    protected function rowEmpty(array $row): bool
    {
        foreach ($row as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }
}
