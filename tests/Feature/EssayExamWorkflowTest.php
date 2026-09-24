<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ApplicationRegistry;
use App\Support\RoleCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Modules\EssayExam\Controllers\EssayExamController;
use Modules\EssayExam\Models\EssayExam;
use Modules\EssayExam\Models\EssayExamDraw;
use Tests\TestCase;

class EssayExamWorkflowTest extends TestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('training_systems', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('specializations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('training_system_id');
            $table->softDeletes();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->unsignedBigInteger('specialization_id');
            $table->softDeletes();
        });
        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('specialization_id');
            $table->softDeletes();
        });
        Schema::create('essay_exams', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('created_by_user_id');
            $table->string('status')->default('DRAFT');
            $table->string('source_document_path')->nullable();
            $table->string('source_pdf_path')->nullable();
            $table->string('exam_type')->default('Tự luận');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->timestamps();
        });
        Schema::create('essay_exam_questions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('essay_exam_id');
            $table->unsignedInteger('question_number');
            $table->unsignedInteger('paper_number')->default(1);
            $table->string('paper_status')->default('DRAFT');
            $table->string('question_type')->default('essay');
            $table->text('content')->nullable();
            $table->text('answer')->nullable();
            $table->json('options')->nullable();
            $table->decimal('points', 6, 2)->default(1);
        });
        Schema::create('essay_exam_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('essay_exam_id');
            $table->unsignedInteger('paper_number')->default(1);
            $table->json('question_ids')->nullable();
            $table->string('draw_code');
            $table->string('qr_code')->nullable();
            $table->string('draw_type');
            $table->string('class_name');
            $table->date('exam_date')->nullable();
            $table->time('exam_time')->nullable();
            $table->string('location')->nullable();
            $table->unsignedBigInteger('drawn_by_user_id')->nullable();
            $table->timestamp('drawn_at');
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('exam_organization_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->date('exam_date');
            $table->time('exam_time')->nullable();
        });

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Khoa A', 'unit_id' => 10],
            ['id' => 2, 'name' => 'Giang vien A', 'unit_id' => 10],
            ['id' => 3, 'name' => 'Giang vien B', 'unit_id' => 20],
        ]);
        DB::table('training_systems')->insert([['id' => 1, 'name' => 'Cao dang'], ['id' => 2, 'name' => 'Trung cap']]);
        DB::table('specializations')->insert([['id' => 1, 'name' => 'Nganh A', 'training_system_id' => 1], ['id' => 2, 'name' => 'Nganh B', 'training_system_id' => 2]]);
        DB::table('classes')->insert([['id' => 1, 'name' => 'Lop A', 'code' => 'A1', 'specialization_id' => 1], ['id' => 2, 'name' => 'Lop B', 'code' => 'B1', 'specialization_id' => 2]]);
        DB::table('subjects')->insert([['id' => 1, 'name' => 'Mon A', 'specialization_id' => 1], ['id' => 2, 'name' => 'Mon B', 'specialization_id' => 2]]);
        DB::table('essay_exams')->insert([
            ['id' => 1, 'code' => 'TMP-A', 'title' => 'A', 'subject_id' => 1, 'class_id' => 1, 'created_by_user_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'TMP-B', 'title' => 'B', 'subject_id' => 2, 'class_id' => 2, 'created_by_user_id' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_faculty_list_is_unit_scoped_and_filters_by_system_major_and_class(): void
    {
        $user = new EssayExamTestUser(['faculty-manager']);
        $user->id = 1;
        $user->unit_id = 10;
        $controller = new EssayExamController();

        $request = Request::create('/essay-exams', 'GET');
        $request->setUserResolver(fn () => $user);
        $this->assertSame([1], $controller->index($request)->getData()['exams']->pluck('id')->all());

        $request = Request::create('/essay-exams', 'GET', ['training_system_id' => 2, 'specialization_id' => 2, 'class_id' => 2]);
        $request->setUserResolver(fn () => $user);
        $this->assertSame([], $controller->index($request)->getData()['exams']->pluck('id')->all());
    }

    public function test_my_exams_never_includes_other_teachers_even_for_manager(): void
    {
        $user = new EssayExamTestUser(['manager']);
        $user->id = 2;
        $request = Request::create('/essay-exams/mine', 'GET', ['teacher_id' => 3]);
        $request->setUserResolver(fn () => $user);

        $this->assertSame([1], (new EssayExamController())->mine($request)->getData()['exams']->pluck('id')->all());
    }

    public function test_both_exam_lists_apply_each_training_filter(): void
    {
        $user = new EssayExamTestUser(['manager']);
        $user->id = 2;
        $controller = new EssayExamController();

        foreach (['training_system_id' => 1, 'specialization_id' => 1, 'class_id' => 1] as $field => $value) {
            $request = Request::create('/essay-exams', 'GET', [$field => $value]);
            $request->setUserResolver(fn () => $user);
            $this->assertSame([1], $controller->index($request)->getData()['exams']->pluck('id')->all());
            $this->assertSame([1], $controller->mine($request)->getData()['exams']->pluck('id')->all());
        }

        foreach (['training_system_id' => 2, 'specialization_id' => 2, 'class_id' => 2] as $field => $value) {
            $request = Request::create('/essay-exams/mine', 'GET', [$field => $value]);
            $request->setUserResolver(fn () => $user);
            $this->assertSame([2], $controller->index($request)->getData()['exams']->pluck('id')->all());
            $this->assertSame([], $controller->mine($request)->getData()['exams']->pluck('id')->all());
        }
    }

    public function test_filter_options_include_catalog_entries_without_exams(): void
    {
        DB::table('training_systems')->insert(['id' => 3, 'name' => 'Quan su']);
        DB::table('specializations')->insert(['id' => 3, 'name' => 'Nganh C', 'training_system_id' => 3]);
        DB::table('classes')->insert(['id' => 3, 'name' => 'Lop C', 'code' => 'C1', 'specialization_id' => 3]);
        $user = new EssayExamTestUser(['instructor']);
        $user->id = 2;
        $controller = new EssayExamController();

        $request = Request::create('/essay-exams/mine', 'GET');
        $request->setUserResolver(fn () => $user);
        $view = $controller->mine($request)->getData();
        $this->assertContains(3, $view['trainingSystems']->pluck('id')->all());
        $this->assertContains(3, $view['specializations']->pluck('id')->all());
        $this->assertContains(3, $view['classes']->pluck('id')->all());

        $request = Request::create('/essay-exams/mine', 'GET', ['class_id' => 3]);
        $request->setUserResolver(fn () => $user);
        $this->assertSame([], $controller->mine($request)->getData()['exams']->pluck('id')->all());
    }

    public function test_paper_code_matches_the_live_draw_format(): void
    {
        $exam = new EssayExam(['code' => 'CĐĐD2B2-B_6720301_M078K7-01']);

        $this->assertSame('CĐĐD2B2-B_6720301_M078K7-01-D03', $exam->paperCode(3));
    }

    public function test_original_pdf_is_accessible_during_review_but_archived_after_approval(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $path = 'essay-exam/imports/original.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');
        $exam = EssayExam::findOrFail(1);
        $exam->update(['source_pdf_path' => $path, 'status' => 'PENDING_DEPT']);
        $owner = new EssayExamTestUser(['instructor']);
        $owner->id = 2;
        $request = Request::create('/essay-exams/1/source-pdf');
        $request->setUserResolver(fn () => $owner);

        $this->assertSame(200, (new EssayExamController())->sourcePdf($request, $exam)->getStatusCode());

        $exam->update(['status' => 'APPROVED']);
        $controller = new EssayExamController();
        $archive = new \ReflectionMethod($controller, 'archiveImportedSources');
        $archive->invoke($controller, $exam, null);
        Storage::disk('local')->assertMissing($path);
        Storage::disk('local')->assertExists($exam->fresh()->source_pdf_path);
        try {
            $controller->sourcePdf($request, $exam->fresh());
            $this->fail('The owner must not see the archived original PDF.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_public_legacy_pdf_is_moved_to_private_storage(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $path = 'essay-exam/imports/legacy.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4');

        $migration = require base_path('modules/EssayExam/Database/Migrations/2026_09_24_000002_secure_essay_exam_files.php');
        $migration->up();

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_text_import_also_creates_a_private_pdf_original(): void
    {
        Storage::fake('local');
        $controller = new EssayExamController();
        $store = new \ReflectionMethod($controller, 'storeImportDocumentAsPdf');
        $paths = $store->invoke($controller, UploadedFile::fake()->createWithContent('de-thi.txt', "Cau 1\nDap an"), 'TMP-TEST');

        $this->assertNotNull($paths['source_pdf_path']);
        Storage::disk('local')->assertExists($paths['source_pdf_path']);
        $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($paths['source_pdf_path']));
    }

    public function test_each_essay_exam_route_uses_a_registered_permission(): void
    {
        $registered = ApplicationRegistry::permissionNames();
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'essay-exams.'));
        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $permissions = collect($route->gatherMiddleware())
                ->filter(fn (string $middleware) => str_starts_with($middleware, 'permission:'))
                ->flatMap(fn (string $middleware) => explode('|', substr($middleware, strlen('permission:'))));
            $this->assertNotEmpty($permissions, $route->getName().' lacks a permission middleware.');
            foreach ($permissions as $permission) {
                $this->assertContains($permission, $registered, $route->getName().' uses an unregistered permission.');
            }
        }
    }

    public function test_instructor_defaults_keep_other_module_permissions_without_shared_exam_list(): void
    {
        $permissions = RoleCatalog::permissionNames(RoleCatalog::INSTRUCTOR);

        $this->assertContains('essay-exams.mine', $permissions);
        $this->assertContains('essay-exams.show', $permissions);
        $this->assertNotContains('essay-exams.index', $permissions);
        $this->assertContains(ApplicationRegistry::permissionNamesFor('lms', ApplicationRegistry::ACTION_VIEW)[0], $permissions);
    }

    public function test_exam_and_answer_print_pages_render_for_an_older_draw(): void
    {
        DB::table('essay_exam_questions')->insert(['essay_exam_id' => 1, 'question_number' => 1, 'paper_number' => 1, 'paper_status' => 'APPROVED', 'content' => 'Question', 'answer' => 'Answer', 'points' => 2]);
        DB::table('essay_exam_draws')->insert(['id' => 1, 'essay_exam_id' => 1, 'paper_number' => 1, 'draw_code' => 'RT-001', 'draw_type' => 'EVEN', 'class_name' => 'Lop A', 'exam_date' => '2026-09-24', 'exam_time' => '08:00', 'drawn_at' => now()->subDays(5), 'created_at' => now(), 'updated_at' => now()]);
        $draw = EssayExamDraw::findOrFail(1);
        $controller = new EssayExamController();

        $examHtml = $controller->printDraw(Request::create('/draw/1/print'), $draw)->render();
        $answersHtml = $controller->printDraw(Request::create('/draw/1/print', 'GET', ['answers' => 1]), $draw)->render();

        $this->assertStringContainsString('Question', $examHtml);
        $this->assertStringNotContainsString('Đáp án/Barem:', $examHtml);
        $this->assertStringContainsString('Answer', $answersHtml);
        $this->assertStringContainsString('Lớp thi: Lop A', $examHtml);
        $this->assertStringContainsString('Tổng điểm: 2,00', $examHtml);
    }

    public function test_draw_rejects_an_exam_plan_for_another_class_or_subject(): void
    {
        DB::table('exam_organization_plans')->insert(['id' => 1, 'class_id' => 2, 'subject_id' => 2, 'exam_date' => today()->toDateString(), 'exam_time' => '08:00']);
        $request = Request::create('/essay-exams/draw', 'POST', [
            'specialization_id' => 1,
            'subject_id' => 1,
            'class_id' => 1,
            'plan_id' => 1,
            'exam_type' => 'Tự luận',
            'draw_type' => 'EVEN',
        ]);

        try {
            (new EssayExamController())->drawStore($request);
            $this->fail('A mismatched exam plan must be rejected.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_draw_uses_an_approved_paper_from_the_selected_class_and_prints_its_number(): void
    {
        DB::table('classes')->insert(['id' => 3, 'name' => 'Lop A2', 'code' => 'A2', 'specialization_id' => 1]);
        DB::table('essay_exams')->where('id', 1)->update(['exam_type' => 'Tự luận']);
        DB::table('essay_exams')->insert(['id' => 3, 'code' => 'TL0003', 'title' => 'Other class', 'subject_id' => 1, 'class_id' => 3, 'created_by_user_id' => 2, 'exam_type' => 'Tự luận', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('essay_exam_questions')->insert([
            ['essay_exam_id' => 1, 'question_number' => 1, 'paper_number' => 2, 'paper_status' => 'APPROVED', 'content' => 'Correct class question'],
            ['essay_exam_id' => 3, 'question_number' => 1, 'paper_number' => 1, 'paper_status' => 'APPROVED', 'content' => 'Other class question'],
        ]);
        DB::table('exam_organization_plans')->insert(['id' => 1, 'class_id' => 1, 'subject_id' => 1, 'exam_date' => today()->addDay()->toDateString(), 'exam_time' => '08:00']);
        $user = new EssayExamTestUser(['manager']);
        $user->id = 1;
        $user->instructor_id = 1;
        $request = Request::create('/essay-exams/draw', 'POST', [
            'specialization_id' => 1, 'subject_id' => 1, 'class_id' => 1,
            'plan_id' => 1, 'exam_type' => 'Tự luận', 'draw_type' => 'EVEN',
        ]);
        $request->setUserResolver(fn () => $user);

        (new EssayExamController())->drawStore($request);

        $draw = EssayExamDraw::query()->firstOrFail();
        $this->assertSame(1, (int) $draw->essay_exam_id);
        $this->assertSame(2, (int) $draw->paper_number);
        $html = (new EssayExamController())->printDraw(Request::create('/draw/'.$draw->id.'/print'), $draw)->render();
        $this->assertStringContainsString('Correct class question', $html);
        $this->assertStringNotContainsString('Other class question', $html);
        $this->assertStringContainsString('TMP-A-D02', $html);
    }

    public function test_faculty_review_is_limited_to_its_unit_and_cannot_use_bgh_print(): void
    {
        $faculty = new EssayExamTestUser(['faculty-manager']);
        $faculty->id = 1;
        $faculty->unit_id = 10;
        $controller = new EssayExamController();
        $review = new \ReflectionMethod($controller, 'canReviewExamStage');

        $this->assertTrue($review->invoke($controller, $faculty, EssayExam::findOrFail(1), 'PENDING_DEPT'));
        $this->assertFalse($review->invoke($controller, $faculty, EssayExam::findOrFail(2), 'PENDING_DEPT'));
        $this->assertFalse($review->invoke($controller, $faculty, EssayExam::findOrFail(1), 'PENDING_BGH'));

        $request = Request::create('/essay-exams/approval/print/1', 'POST', ['print_mode' => 'unsigned']);
        $request->setUserResolver(fn () => $faculty);
        try {
            $controller->savePrintedApprovalDocument($request, EssayExam::findOrFail(1));
            $this->fail('Faculty must not finalize a BGH print.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}

class EssayExamTestUser extends User
{
    public function __construct(private array $roleNames = [])
    {
        parent::__construct();
    }

    public function hasAnyRole(...$roles): bool
    {
        return (bool) array_intersect($this->roleNames, (array) ($roles[0] ?? []));
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        return (bool) array_intersect($this->roleNames, (array) $roles);
    }
}
