<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\MilitaryRank;
use App\Models\User;
use App\Support\WordExportTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ExportTemplates\Services\BuilderTemplateSchema;
use Modules\Grades\Support\GradeSettings;
use Modules\Lms\Support\LmsSettings;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SharedSettingsAndMilitaryRankTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_settings_and_rank_rules_are_connected(): void
    {
        $adminRole = Role::findOrCreate('super-admin', 'web');
        $studentRole = Role::findOrCreate('student', 'web');
        $customRole = Role::findOrCreate('schedule-coordinator', 'web');

        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'user_type' => 'internal_user',
            'status' => 1,
        ]);
        $admin->syncRoles([$adminRole]);

        $this->actingAs($admin)->get('/settings')
            ->assertOk()
            ->assertSee('Danh mục năm học dùng chung')
            ->assertSee('data-settings-hub-root', false)
            ->assertSee('data-settings-hub-trigger="dashboard-identity"', false)
            ->assertSee('data-settings-hub-trigger="academic"', false)
            ->assertSee('system-settings--dashboard', false)
            ->assertSeeInOrder(['Thông tin tài khoản', 'Cài đặt']);
        $this->actingAs($admin)->get('/lms/settings')
            ->assertOk()
            ->assertSee('Cài đặt LMS')
            ->assertSee('data-settings-hub-trigger="lms-course"', false)
            ->assertSee('data-settings-hub-trigger="lms-exams"', false)
            ->assertSee('system-settings--lms', false);
        $this->actingAs($admin)->get('/grades/settings')
            ->assertOk()
            ->assertSee('Cài đặt Quản lý điểm')
            ->assertSee('data-settings-hub-trigger="grades-scale"', false)
            ->assertSee('data-settings-hub-trigger="grades-weights"', false)
            ->assertSee('system-settings--grades', false);
        $this->actingAs($admin)->get('/users/create')
            ->assertOk()
            ->assertSee('Cấp bậc')
            ->assertSeeText('Quản lý khảo thí');

        $this->actingAs($admin)->put('/settings/dashboard/general', [
            'parent_organization_name' => 'CƠ QUAN CẤP TRÊN DEMO',
            'organization_name' => 'ĐƠN VỊ DEMO',
            'national_heading' => 'QUỐC HIỆU DEMO',
            'national_motto' => 'TIÊU NGỮ DEMO',
            'document_location' => 'TP Demo',
            'default_export_format' => 'word',
            'default_page_size' => 'A3',
            'default_orientation' => 'portrait',
        ])->assertSessionHasNoErrors();

        $this->assertStringContainsString('CƠ QUAN CẤP TRÊN DEMO', WordExportTemplate::defaultHeaderLeft());
        $this->assertStringContainsString('QUỐC HIỆU DEMO', WordExportTemplate::defaultHeaderRight());
        $builderDefaults = BuilderTemplateSchema::empty('word');
        $this->assertSame('A3', $builderDefaults['page']['size']);
        $this->assertSame('portrait', $builderDefaults['page']['orientation']);

        $this->actingAs($admin)->put('/settings/lms/general', [
            'default_course_status' => 'published',
            'default_assignment_max_score' => 20,
            'submission_max_file_mb' => 25,
            'allow_late_by_default' => 1,
            'default_exam_duration_minutes' => 60,
            'default_exam_attempts' => 2,
            'default_exam_pass_score' => 6,
            'shuffle_questions_by_default' => 0,
            'notify_assignment_graded' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertSame('published', LmsSettings::courseStatus());
        $this->assertSame(20.0, LmsSettings::assignmentMaxScore());
        $this->assertSame(25, LmsSettings::submissionMaxMegabytes());
        $this->assertTrue(LmsSettings::allowLateByDefault());
        $this->assertSame(60, LmsSettings::examDurationMinutes());
        $this->assertSame(2, LmsSettings::examAttempts());
        $this->assertFalse(LmsSettings::shuffleQuestions());
        $this->assertFalse(LmsSettings::notifyAssignmentGraded());

        $this->actingAs($admin)->put('/settings/grades/general', [
            'max_score' => 10,
            'pass_score' => 5,
            'excellent_score' => 8.5,
            'decimal_places' => 2,
            'rounding_mode' => 'half_down',
            'weight_oral_15' => 10,
            'weight_period_1' => 20,
            'weight_midterm' => 30,
            'weight_final' => 40,
        ])->assertSessionHasNoErrors();

        $this->assertSame(10.0, GradeSettings::maxScore());
        $this->assertSame(8.5, GradeSettings::excellentScore());
        $this->assertSame(2, GradeSettings::decimalPlaces());
        $this->assertSame('half_down', GradeSettings::roundingMode());
        $this->assertSame(7.12, GradeSettings::round(7.125));
        $this->assertSame(1.0, array_sum(GradeSettings::columnWeights()));

        $newStartYear = (int) AcademicYear::query()->max('start_year') + 1;
        $this->actingAs($admin)->post('/settings/academic-years', [
            'start_year' => $newStartYear,
            'is_current' => 1,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('academic_years', [
            'code' => $newStartYear.'-'.($newStartYear + 1),
            'is_current' => 1,
            'is_active' => 1,
        ]);
        $this->assertSame(1, AcademicYear::query()->where('is_current', true)->count());

        $rank = MilitaryRank::query()->where('code', 'colonel')->firstOrFail();
        $this->actingAs($admin)->post('/users', [
            'name' => 'Quản trị có cấp bậc',
            'email' => 'ranked-admin@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => $adminRole->id,
            'military_rank_id' => $rank->id,
            'user_type' => 'internal_user',
            'status' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'ranked-admin@example.test',
            'military_rank_id' => $rank->id,
        ]);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Điều phối viên có cấp bậc',
            'email' => 'ranked-custom-role@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => $customRole->id,
            'military_rank_id' => $rank->id,
            'user_type' => 'internal_user',
            'status' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'ranked-custom-role@example.test',
            'military_rank_id' => $rank->id,
        ]);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Tài khoản không hợp lệ',
            'email' => 'invalid-rank@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => $studentRole->id,
            'military_rank_id' => $rank->id,
            'user_type' => 'student',
            'status' => 1,
        ])->assertSessionHasErrors('military_rank_id');

        $this->assertDatabaseMissing('users', ['email' => 'invalid-rank@example.test']);
    }
}
