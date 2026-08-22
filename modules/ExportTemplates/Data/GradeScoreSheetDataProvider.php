<?php

namespace Modules\ExportTemplates\Data;

use Modules\ExportTemplates\Contracts\TemplateDataProviderInterface;
use Modules\Grades\Models\GradeBook;
use Modules\Grades\Models\GradeCell;
use Modules\Grades\Services\GradeBookService;

class GradeScoreSheetDataProvider implements TemplateDataProviderInterface
{
    public function __construct(private readonly GradeBookService $gradeBookService) {}

    public function featureKey(): string
    {
        return 'grades.score_sheet';
    }

    public function schema(): array
    {
        $field = static fn (string $key, string $label, string $type = 'string'): array => compact('key', 'label', 'type');
        $group = static fn (string $key, string $label, array $children, string $type = 'object'): array => compact('key', 'label', 'type', 'children');

        return [
            'version' => 1,
            'feature_key' => $this->featureKey(),
            'groups' => [
                $group('grade_book', 'Thông tin bảng điểm', [
                    $field('grade_book.title', 'Tên bảng điểm'),
                    $field('grade_book.status', 'Trạng thái'),
                    $field('grade_book.academic_year', 'Năm học'),
                    $field('grade_book.class.name', 'Tên lớp'),
                    $field('grade_book.class.code', 'Mã lớp'),
                    $field('grade_book.subject.name', 'Môn học'),
                    $field('grade_book.instructor.name', 'Giảng viên'),
                ]),
                $group('columns', 'Cột điểm', [
                    $field('columns[].code', 'Mã cột'),
                    $field('columns[].name', 'Tên cột'),
                    $field('columns[].max_score', 'Điểm tối đa', 'number'),
                ], 'collection'),
                $group('students', 'Danh sách học viên', [
                    $field('students[].index', 'STT', 'number'),
                    $field('students[].id', 'ID học viên', 'number'),
                    $field('students[].code', 'Mã học viên'),
                    $field('students[].name', 'Họ tên'),
                    $field('students[].scores', 'Điểm theo cột', 'object'),
                ], 'collection'),
                $group('generated', 'Thông tin tạo tài liệu', [
                    $field('generated.at', 'Thời điểm tạo', 'date'),
                    $field('generated.by', 'Người tạo'),
                ]),
            ],
        ];
    }

    public function mockData(): array
    {
        return [
            'grade_book' => [
                'title' => 'Bảng điểm mẫu', 'status' => 'Đang nhập', 'academic_year' => '2025-2026',
                'class' => ['name' => 'Y54', 'code' => 'Y54'],
                'subject' => ['name' => 'Chiến thuật bộ binh'],
                'instructor' => ['name' => 'Nguyễn Văn Giảng'],
            ],
            'columns' => [
                ['code' => 'oral_15', 'name' => 'Kiểm tra 15 phút', 'max_score' => 10],
                ['code' => 'final', 'name' => 'Điểm thi', 'max_score' => 10],
            ],
            'students' => [
                ['index' => 1, 'id' => 1, 'code' => 'HV001', 'name' => 'Nguyễn Văn A', 'scores' => ['oral_15' => 8, 'final' => 9]],
                ['index' => 2, 'id' => 2, 'code' => 'HV002', 'name' => 'Trần Văn B', 'scores' => ['oral_15' => 7.5, 'final' => 8]],
            ],
            'generated' => ['at' => now()->format('Y-m-d H:i:s'), 'by' => 'Hệ thống'],
        ];
    }

    public function load(array $context): array
    {
        $book = GradeBook::query()->with(['columns', 'classModel', 'subject', 'instructor'])->findOrFail((int) ($context['grade_book_id'] ?? 0));
        $students = $this->gradeBookService->studentsForBook($book);
        $cells = GradeCell::query()->where('grade_book_id', $book->id)->get()->groupBy('user_id')->map(fn ($rows) => $rows->keyBy('grade_column_id'));

        return [
            'grade_book' => [
                'title' => (string) $book->title, 'status' => $book->statusLabel(), 'academic_year' => (string) ($book->academic_year ?? ''),
                'class' => ['name' => (string) ($book->classModel?->name ?? ''), 'code' => (string) ($book->classModel?->code ?? '')],
                'subject' => ['name' => (string) ($book->subject?->name ?? '')],
                'instructor' => ['name' => (string) ($book->instructor?->name ?? '')],
            ],
            'columns' => $book->columns->map(fn ($column) => ['code' => (string) $column->code, 'name' => (string) $column->name, 'max_score' => (float) ($column->max_score ?? 10)])->values()->all(),
            'students' => $students->values()->map(fn ($student, $index) => [
                'index' => $index + 1, 'id' => (int) $student->id, 'code' => (string) $student->code, 'name' => (string) $student->name,
                'scores' => $book->columns->mapWithKeys(fn ($column) => [$column->code => $cells[$student->id][$column->id]->score ?? null])->all(),
            ])->all(),
            'generated' => ['at' => now()->format('Y-m-d H:i:s'), 'by' => (string) ($context['generated_by'] ?? auth()->user()?->name ?? 'Hệ thống')],
        ];
    }
}
