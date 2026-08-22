<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Import bài học (đơn giản) - thay cho luồng khung CTĐT nguyên khối cũ trên
 * cùng route (subject-lessons.import.store). Bắt buộc chọn Ngành → Môn
 * trên form trước khi import; cột "Mã môn học" trong file (nếu điền) chỉ
 * dùng để đối chiếu với môn đã chọn. "Mã bài cha" phân cấp Unit/Chương ->
 * Bài con trong cùng môn.
 */
class LessonsImportTest extends TestCase
{
    use RefreshDatabase;

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        foreach (['subject-lessons.index', 'subject-lessons.create'] as $permissionName) {
            $permission = Permission::findOrCreate($permissionName, 'web');
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function makeSubject(string $code): Subject
    {
        $spec = Specialization::query()->create([
            'name' => 'Ngành Test Import Bài',
            'code' => 'SPEC-'.uniqid(),
            'level' => Specialization::LEVEL_BEGINNER,
            'duration_months' => 12,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true,
        ]);

        return Subject::query()->create([
            'name' => 'Môn Test Import Bài',
            'code' => $code,
            'specialization_id' => $spec->id,
            'credits' => 1,
            'theory_hours' => 1,
            'practice_hours' => 0,
            'self_study_hours' => 0,
            'level' => 'basic',
            'assessment_method' => 'exam',
            'is_required' => true,
            'is_active' => true,
        ]);
    }

    private function buildXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(array_merge([
            ['Mã môn học', 'Mã bài học', 'Mã bài cha', 'Tên bài học', 'Loại bài', 'Thứ tự', 'Giờ lý thuyết', 'Giờ thực hành', 'Giờ thi', 'Học kỳ', 'Mô tả'],
        ], $rows), null, 'A1');

        $path = storage_path('app/test-lessons-import-'.uniqid().'.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function uploadedFile(string $path): UploadedFile
    {
        return new UploadedFile($path, 'lessons.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_import_form_requires_specialization_and_subject_selection(): void
    {
        $path = $this->buildXlsx([
            ['', 'B01', '', 'Bài 1', 'Bài', 1, 1, 0, 0, 'Học kỳ 1', ''],
        ]);

        try {
            $this->actingAs($this->authorizedUser())
                ->post(route('subject-lessons.import.store'), [
                    'file' => $this->uploadedFile($path),
                ])
                ->assertSessionHasErrors(['specialization_id', 'subject_id']);
        } finally {
            @unlink($path);
        }
    }

    public function test_import_creates_lessons_with_parent_child_nesting(): void
    {
        $subject = $this->makeSubject('LESSONTEST01');

        $path = $this->buildXlsx([
            ['LESSONTEST01', 'U1', '', 'Unit 1', 'Unit', 1, 0, 0, 0, 'Học kỳ 1', ''],
            ['LESSONTEST01', 'U1-B1', 'U1', 'Bài con 1', 'Bài con', 1, 4, 0, 0, 'Học kỳ 1', ''],
            ['', 'B02', '', 'Bài lẻ (không điền mã môn, dùng môn đã chọn trên form)', 'Bài', 2, 2, 6, 0, 'Học kỳ 1', ''],
        ]);

        try {
            $response = $this->actingAs($this->authorizedUser())
                ->post(route('subject-lessons.import.store'), [
                    'specialization_id' => $subject->specialization_id,
                    'subject_id' => $subject->id,
                    'file' => $this->uploadedFile($path),
                ]);

            $response->assertRedirect(route('subject-lessons.index'));

            $this->assertDatabaseHas('subject_lessons', [
                'subject_id' => $subject->id,
                'code' => 'U1',
                'lesson_kind' => SubjectLesson::KIND_UNIT,
                'parent_id' => null,
            ]);

            $unit = SubjectLesson::where('subject_id', $subject->id)->where('code', 'U1')->firstOrFail();

            $this->assertDatabaseHas('subject_lessons', [
                'subject_id' => $subject->id,
                'code' => 'U1-B1',
                'lesson_kind' => SubjectLesson::KIND_SUB,
                'parent_id' => $unit->id,
                'total_hours' => 4,
            ]);

            $this->assertDatabaseHas('subject_lessons', [
                'subject_id' => $subject->id,
                'code' => 'B02',
                'lesson_kind' => SubjectLesson::KIND_LESSON,
                'parent_id' => null,
                'total_hours' => 8,
            ]);
        } finally {
            @unlink($path);
        }
    }

    public function test_reimporting_the_same_file_updates_instead_of_duplicating(): void
    {
        $subject = $this->makeSubject('LESSONTEST02');
        $path = $this->buildXlsx([
            ['LESSONTEST02', 'B01', '', 'Bài 1', 'Bài', 1, 2, 0, 0, 'Học kỳ 1', ''],
        ]);

        try {
            $user = $this->authorizedUser();
            $payload = [
                'specialization_id' => $subject->specialization_id,
                'subject_id' => $subject->id,
            ];

            $this->actingAs($user)->post(route('subject-lessons.import.store'), $payload + [
                'file' => $this->uploadedFile($path),
            ])->assertRedirect();

            $this->actingAs($user)->post(route('subject-lessons.import.store'), $payload + [
                'file' => $this->uploadedFile($path),
            ])->assertRedirect();

            $this->assertSame(1, SubjectLesson::where('subject_id', $subject->id)->count());
        } finally {
            @unlink($path);
        }
    }

    public function test_row_with_subject_code_mismatching_selected_subject_is_rejected(): void
    {
        $subject = $this->makeSubject('LESSONTEST03');
        $otherSubject = $this->makeSubject('OTHERSUBJ01');
        $path = $this->buildXlsx([
            ['LESSONTEST03', 'B01', '', 'Bài đúng môn', 'Bài', 1, 1, 0, 0, 'Học kỳ 1', ''],
            [$otherSubject->code, 'B02', '', 'Bài khai nhầm mã môn khác', 'Bài', 2, 1, 0, 0, 'Học kỳ 1', ''],
        ]);

        try {
            $response = $this->actingAs($this->authorizedUser())
                ->post(route('subject-lessons.import.store'), [
                    'specialization_id' => $subject->specialization_id,
                    'subject_id' => $subject->id,
                    'file' => $this->uploadedFile($path),
                ]);

            $response->assertRedirect(route('subject-lessons.index'));
            $this->assertDatabaseHas('subject_lessons', ['subject_id' => $subject->id, 'code' => 'B01']);
            $this->assertDatabaseMissing('subject_lessons', ['code' => 'B02']);
        } finally {
            @unlink($path);
        }
    }

    public function test_download_template_returns_an_xlsx_file(): void
    {
        $this->actingAs($this->authorizedUser())
            ->get(route('subject-lessons.import.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
