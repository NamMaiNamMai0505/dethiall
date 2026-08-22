<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use App\Support\ManagerUnitScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Database\Seeders\StandardHoursDemoSeeder;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Models\Position;
use Modules\StandardHours\Models\ResearchCategory;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Models\YearlyResult;
use Modules\StandardHours\Services\ConversionService;
use Modules\StandardHours\Services\PeriodService;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StandardHoursDemoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_demo_supports_instructor_and_direct_reviewer_workflows(): void
    {
        Storage::fake('public');

        $actor = User::factory()->create([
            'email' => 'standard-hours-setup@example.test',
            'role_id' => 1,
        ]);
        $unit = Unit::query()->where('code', 'K7')->firstOrFail();
        $instructor = Instructor::query()->create([
            'name' => 'Nguyễn Văn A',
            'code' => 'GV-0001',
            'email' => 'gv-0001@example.test',
            'unit_id' => $unit->id,
            'status' => Instructor::STATUS_ACTIVE,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $outsideUnit = Unit::query()->where('code', 'K1')->firstOrFail();
        Instructor::query()->create([
            'name' => 'Giảng viên ngoài khoa',
            'code' => 'GV-OUTSIDE',
            'email' => 'gv-outside@example.test',
            'unit_id' => $outsideUnit->id,
            'status' => Instructor::STATUS_ACTIVE,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->seed(StandardHoursDemoSeeder::class);
        $this->seed(StandardHoursDemoSeeder::class);

        $instructorUser = User::query()
            ->where('email', StandardHoursDemoSeeder::INSTRUCTOR_EMAIL)
            ->firstOrFail();
        $reviewer = User::query()
            ->where('email', StandardHoursDemoSeeder::REVIEWER_EMAIL)
            ->firstOrFail();
        $instructor->refresh();

        $this->assertTrue(Hash::check('password', $instructorUser->password));
        $this->assertTrue(Hash::check('password', $reviewer->password));
        $this->assertTrue($instructorUser->hasRole('instructor'));
        $this->assertTrue($reviewer->hasRole('manager'));
        $this->assertTrue($instructorUser->can('standard-hours.create'));
        $this->assertTrue($reviewer->can('standard-hours.edit'));
        $this->assertSame($instructor->unit_id, $reviewer->unit_id);

        $conversionRecords = ConversionRecord::query()
            ->where('activity_name', 'like', StandardHoursDemoSeeder::DEMO_MARKER.'%')
            ->get();
        $researchRecords = ResearchRecord::query()
            ->where('product_name', 'like', StandardHoursDemoSeeder::DEMO_MARKER.'%')
            ->get();

        $this->assertCount(5, $conversionRecords);
        $this->assertCount(6, $researchRecords);
        $this->assertEqualsCanonicalizing(
            ['approved', 'draft', 'rejected', 'submitted'],
            $conversionRecords->pluck('status')->unique()->values()->all()
        );
        $this->assertEqualsCanonicalizing(
            ['approved', 'draft', 'rejected', 'submitted'],
            $researchRecords->pluck('status')->unique()->values()->all()
        );

        Storage::disk('public')->assertExists('standard-hours/demo/minh-chung-hoat-dong-chuyen-mon.docx');
        Storage::disk('public')->assertExists('standard-hours/demo/minh-chung-nghien-cuu-khoa-hoc.docx');

        $yearlyResult = YearlyResult::query()
            ->where('instructor_id', $instructor->id)
            ->where('year', StandardHoursDemoSeeder::YEAR)
            ->firstOrFail();
        $this->assertSame(YearlyResult::STATUS_CALCULATED, $yearlyResult->status);
        $this->assertGreaterThanOrEqual(0, (float) $yearlyResult->teaching_hours);
        $this->assertGreaterThan(0, (float) $yearlyResult->conversion_hours);
        $this->assertGreaterThan(0, (float) $yearlyResult->research_hours);

        $this->actingAs($instructorUser)
            ->get('/standard-hours')
            ->assertOk();
        $this->get('/standard-hours/guide')
            ->assertOk()
            ->assertSee('giangvien@example.com');
        $this->get('/standard-hours/conversion-records?year=2026')
            ->assertOk()
            ->assertSee('Chờ thẩm định')
            ->assertSee('Đã thẩm định');
        $this->get('/standard-hours/research-records?year=2026')
            ->assertOk()
            ->assertSee('Chờ thẩm định')
            ->assertSee('Đã thẩm định');
        $this->get('/standard-hours/research-records/create')
            ->assertOk()
            ->assertSee('Tổng số thành viên tham gia')
            ->assertSee('Số giờ bạn kê khai');
        $this->get('/standard-hours/my-results?year=2026')->assertOk();
        $this->get('/standard-hours/calculations?year=2026')->assertForbidden();

        $researchCategory = ResearchCategory::query()->where('code', 'NCKH-10')->firstOrFail();
        $manualHours = 12.5;
        $this->post('/standard-hours/research-records', [
            'instructor_id' => $instructor->id,
            'research_category_id' => $researchCategory->id,
            'product_name' => '[DEMO PERSONAL] Sản phẩm tự kê khai phần đóng góp',
            'acceptance_date' => '2026-07-29',
            'member_count' => 3,
            'participation_type' => ResearchRecord::PARTICIPATION_MEMBER,
            'contribution_percent' => 40,
            'duration_years' => 2,
            'converted_hours' => $manualHours,
            'hours_adjustment_note' => 'Phân chia theo tỷ lệ đóng góp thực tế.',
            'status' => ResearchRecord::STATUS_DRAFT,
        ])->assertRedirect();

        $personalRecord = ResearchRecord::query()
            ->where('product_name', '[DEMO PERSONAL] Sản phẩm tự kê khai phần đóng góp')
            ->firstOrFail();
        $annualProductHours = round((float) $researchCategory->research_hours / 2, 2);
        $this->assertSame($instructor->id, $personalRecord->instructor_id);
        $this->assertSame(3, $personalRecord->member_count);
        $this->assertSame(ResearchRecord::PARTICIPATION_MEMBER, $personalRecord->participation_type);
        $this->assertEquals($annualProductHours, (float) $personalRecord->annual_product_hours);
        $this->assertEquals(round($annualProductHours * 0.4, 2), (float) $personalRecord->calculated_hours);
        $this->assertEquals($manualHours, (float) $personalRecord->converted_hours);
        $this->assertTrue($personalRecord->has_hours_adjustment);
        $this->assertCount(1, $personalRecord->members);
        $this->assertSame($instructor->id, $personalRecord->members->first()->instructor_id);

        $this->post('/standard-hours/research-records', [
            'instructor_id' => $instructor->id,
            'research_category_id' => $researchCategory->id,
            'product_name' => '[DEMO PERSONAL] Chủ nhiệm theo luật',
            'acceptance_date' => '2026-07-29',
            'member_count' => 3,
            'participation_type' => ResearchRecord::PARTICIPATION_LEAD,
            'duration_years' => 1,
            'converted_hours' => 1,
            'hours_adjustment_note' => 'Giá trị này phải bị bỏ qua với chủ nhiệm.',
            'status' => ResearchRecord::STATUS_DRAFT,
        ])->assertRedirect();

        $leadRecord = ResearchRecord::query()
            ->where('product_name', '[DEMO PERSONAL] Chủ nhiệm theo luật')
            ->firstOrFail();
        $this->assertEquals(
            round((float) $researchCategory->research_hours * 0.5, 2),
            (float) $leadRecord->converted_hours
        );
        $this->assertNull($leadRecord->hours_adjustment_note);

        $objectType02 = ObjectType::query()->where('code', '02')->firstOrFail();
        $headOfFaculty = Position::query()->where('name', 'Chủ nhiệm khoa')->firstOrFail();
        $this->assertEquals(60, (float) $headOfFaculty->ratio_percent);

        $this->get('/standard-hours/my-results/declaration/edit?year=2027')
            ->assertOk()
            ->assertSee($instructor->code)
            ->assertSee('Truy xuất số giờ giảng từ lịch huấn luyện')
            ->assertSee('Giờ giảng dạy khác được tính trực tiếp');

        $this->postJson('/standard-hours/my-results/declaration/retrieve-schedule', [
            'from_date' => '2027-01-01',
            'to_date' => '2027-12-31',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'total_hours',
                'total_periods',
                'rows',
                'by_subject',
                'conversion_hours',
                'research_hours',
            ]);

        $this->postJson('/standard-hours/my-results/declaration/retrieve-schedule', [
            'from_date' => '2026-12-01',
            'to_date' => '2027-02-28',
        ])
            ->assertOk()
            ->assertJsonStructure(['total_hours', 'rows', 'by_subject']);

        $this->post('/standard-hours/my-results/declaration', [
            'declaration_from_date' => '2027-01-01',
            'declaration_to_date' => '2027-12-31',
            'object_type_id' => $objectType02->id,
            'position_id' => $headOfFaculty->id,
            'other_teaching_hours' => 7.5,
            'other_teaching_notes' => 'Giờ giảng chuyên đề ngoài lịch huấn luyện.',
        ])->assertRedirect();

        $annualDeclaration = YearlyResult::query()
            ->where('instructor_id', $instructor->id)
            ->where('year', 2027)
            ->firstOrFail();
        $this->assertSame($objectType02->id, $annualDeclaration->object_type_id);
        $this->assertSame($headOfFaculty->id, $annualDeclaration->position_id);
        $this->assertEquals(228, (float) $annualDeclaration->standard_norm_hours);
        $this->assertEquals(7.5, (float) $annualDeclaration->other_teaching_hours);
        $this->assertSame(YearlyResult::DECLARATION_SUBMITTED, $annualDeclaration->declaration_status);
        $this->assertEquals(
            (float) $annualDeclaration->schedule_teaching_hours,
            (float) $annualDeclaration->teaching_hours
        );
        $this->assertIsArray($annualDeclaration->schedule_teaching_details);
        $this->assertArrayHasKey('rows', $annualDeclaration->schedule_teaching_details);
        $this->assertSame($instructorUser->id, $annualDeclaration->declared_by);

        $this->get('/standard-hours/my-results/'.$annualDeclaration->id)
            ->assertOk()
            ->assertSee('Thống kê Lịch huấn luyện đã truy xuất')
            ->assertSee('Giờ giảng chuyên đề ngoài lịch huấn luyện.');

        $this->actingAs($reviewer);
        $this->patch('/standard-hours/calculations/'.$annualDeclaration->id.'/review-declaration', [
            'declaration_status' => YearlyResult::DECLARATION_APPROVED,
        ])->assertRedirect();
        $annualDeclaration->refresh();
        $this->assertSame(YearlyResult::DECLARATION_APPROVED, $annualDeclaration->declaration_status);
        $this->assertEquals(
            (float) $annualDeclaration->schedule_teaching_hours + 7.5,
            (float) $annualDeclaration->teaching_hours
        );

        $this->get('/standard-hours')->assertOk();
        $this->get('/standard-hours')
            ->assertSee('Hồ sơ kê khai giờ chuẩn');
        $this->get('/standard-hours/my-results?from_date=2026-01-01&to_date=2027-12-31')
            ->assertOk()
            ->assertSee('Tất cả giảng viên')
            ->assertSee('Bộ lọc ngày là khoảng tra cứu độc lập');
        $this->get('/standard-hours/my-results/declaration/edit?year=2027&instructor_id='.$instructor->id)
            ->assertOk()
            ->assertSee($instructor->code)
            ->assertSee('Admin đang lập/cập nhật hồ sơ');
        $this->get('/standard-hours/guide')->assertOk();
        $this->get('/standard-hours/conversion-records?year=2026&status=submitted')->assertOk();
        $this->get('/standard-hours/research-records?year=2026&status=submitted')->assertOk();
        $this->get('/standard-hours/calculations?year=2026')->assertOk();
        $this->get('/standard-hours/department-overtime?year=2026')->assertOk();
        $this->get('/standard-hours/reports?year=2026')->assertOk();
        $this->patch('/standard-hours/calculations/rollback', ['year' => 2027])
            ->assertRedirect();
        $this->assertDatabaseHas('yearly_standard_results', [
            'id' => $annualDeclaration->id,
            'declared_by' => $instructorUser->id,
        ]);

        $visibleConversions = app(ConversionService::class)
            ->paginate(['year' => '2026', 'per_page' => 50])
            ->getCollection();
        $this->assertTrue($visibleConversions->every(
            fn (ConversionRecord $record) => $record->instructor?->unit_id === $reviewer->unit_id
        ));

        $outsideInstructor = Instructor::query()
            ->where('unit_id', '!=', $reviewer->unit_id)
            ->first();
        if ($outsideInstructor) {
            $this->assertFalse(ManagerUnitScope::canAccessInstructor($outsideInstructor, $reviewer));
        }
    }

    public function test_standard_hours_period_switch_uses_shared_academic_year_dates(): void
    {
        $academicYear = AcademicYear::query()->updateOrCreate(
            ['code' => '2028-2029'],
            [
                'start_year' => 2028,
                'end_year' => 2029,
                'name' => 'Năm học 2028-2029',
                'starts_at' => '2028-08-15',
                'ends_at' => '2029-07-20',
                'is_current' => false,
                'is_active' => true,
                'sort_order' => 2028,
            ]
        );

        $periodService = app(PeriodService::class);
        $admin = User::factory()->create(['email' => 'period-setting-admin@example.test']);
        $admin->assignRole(Role::findOrCreate('super-admin', 'web'));
        $periodModePage = $this->actingAs($admin)
            ->get('/standard-hours/settings/period-mode');

        $periodModePage
            ->assertOk()
            ->assertSee('CẤU HÌNH KỲ TÍNH GIỜ CHUẨN GV')
            ->assertSee('data-period-submit', false);
        $this->assertMatchesRegularExpression(
            '/<form\b(?=[^>]*data-period-mode-form)(?=[^>]*data-turbo="true")(?=[^>]*data-turbo-action="replace")[^>]*>/s',
            $periodModePage->getContent()
        );

        $this->put('/standard-hours/settings/period-mode', [
            'period_mode' => PeriodService::MODE_ACADEMIC_YEAR,
        ])
            ->assertStatus(303)
            ->assertRedirect('/standard-hours/settings/period-mode');

        $periodService->setMode(PeriodService::MODE_ACADEMIC_YEAR);

        $this->assertSame(PeriodService::MODE_ACADEMIC_YEAR, $periodService->mode());
        $this->assertSame('Năm học', $periodService->modeLabel());
        $this->assertSame('2028-2029', $periodService->label(2028));
        $this->assertSame(
            [
                $academicYear->starts_at->toDateString(),
                $academicYear->ends_at->toDateString(),
            ],
            $periodService->dateRange(2028)
        );
        $this->assertSame(2028, $periodService->resolveYearForDate('2029-02-01'));
        $this->assertTrue($periodService->rangeBelongsToPeriod('2028-09-01', '2029-06-30'));
        $this->assertFalse($periodService->rangeBelongsToPeriod('2028-07-01', '2028-09-01'));
    }
}
