<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ExamOrganization\Controllers\ExamOrganizationController;
use Tests\TestCase;

class ExamOrganizationGraderPlanningTest extends TestCase
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

        Schema::create('exam_organization_plans', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('instructors', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('conversion_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('unit');
            $table->string('conversion_method');
            $table->decimal('coefficient', 8, 2);
            $table->boolean('is_active');
            $table->softDeletes();
        });
        Schema::create('exam_organization_actions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->string('action_type');
            $table->string('name');
            $table->unsignedBigInteger('instructor_id');
            $table->unsignedBigInteger('conversion_category_id');
            $table->unsignedInteger('planned_papers')->nullable();
            $table->decimal('converted_hours', 8, 2)->nullable();
            $table->string('role');
            $table->string('note')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    public function test_each_grader_keeps_a_separate_planned_paper_count(): void
    {
        DB::table('exam_organization_plans')->insert(['id' => 1]);
        DB::table('instructors')->insert([['id' => 101], ['id' => 102]]);
        DB::table('conversion_categories')->insert([
            'id' => 1,
            'name' => 'Chấm bài thi',
            'unit' => 'Bài',
            'conversion_method' => 'coefficient',
            'coefficient' => 0.2,
            'is_active' => true,
        ]);

        $request = Request::create('/exam-organization/actions', 'POST', [
            'plan_id' => 1,
            'action_type' => 'ASSIGNMENT',
            'role' => 'GRADER',
            'grader_count' => 2,
            'graders' => [
                ['instructor_id' => 101, 'conversion_category_id' => 1, 'planned_papers' => 3],
                ['instructor_id' => 102, 'conversion_category_id' => 1, 'planned_papers' => 2],
            ],
        ]);
        $request->setUserResolver(fn () => new class {
            public function can(string $permission): bool { return true; }
        });

        app(ExamOrganizationController::class)->action($request);

        $this->assertDatabaseHas('exam_organization_actions', ['plan_id' => 1, 'role' => 'GRADER', 'instructor_id' => 101, 'planned_papers' => 3, 'converted_hours' => null]);
        $this->assertDatabaseHas('exam_organization_actions', ['plan_id' => 1, 'role' => 'GRADER', 'instructor_id' => 102, 'planned_papers' => 2, 'converted_hours' => null]);
        $this->assertSame(2, DB::table('exam_organization_actions')->count());
    }
}
