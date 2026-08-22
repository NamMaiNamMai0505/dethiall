<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Database\Seeders\StandardHoursDemoSeeder;
use Modules\StandardHours\Models\ConversionCategory;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Models\ExternalActivityRecord;
use Modules\StandardHours\Models\ResearchCategory;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Models\YearlyResult;
use Modules\Unit\Models\Unit;
use Tests\TestCase;

class LmsStandardHoursCloneTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(): array
    {
        Storage::fake('public');

        $actor = User::factory()->create([
            'email' => 'lms-sh-setup@example.test',
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

        $this->seed(StandardHoursDemoSeeder::class);

        $instructorUser = User::query()
            ->where('email', StandardHoursDemoSeeder::INSTRUCTOR_EMAIL)
            ->firstOrFail();
        $instructor->refresh();

        return [$instructorUser, $instructor];
    }

    public function test_instructor_can_reach_lms_standard_hours_hub_and_my_results(): void
    {
        [$instructorUser] = $this->seedDemo();

        $this->actingAs($instructorUser)
            ->get('/lms/gv/standard-hours')
            ->assertOk()
            ->assertSee('Giờ chuẩn giảng viên');

        $this->get('/lms/gv/standard-hours/my-results')
            ->assertOk()
            ->assertSee('Kê khai giờ chuẩn hằng năm');

        $this->get('/lms/gv/standard-hours/guide')
            ->assertOk();
    }

    public function test_instructor_cannot_see_another_instructors_yearly_result(): void
    {
        [$instructorUser, $instructor] = $this->seedDemo();

        $actor = User::query()->where('email', 'lms-sh-setup@example.test')->firstOrFail();
        $otherUnit = Unit::query()->where('code', '!=', $instructor->unit->code)->firstOrFail();
        $otherInstructor = Instructor::query()->create([
            'name' => 'GV khác',
            'code' => 'GV-OTHER',
            'email' => 'gv-other@example.test',
            'unit_id' => $otherUnit->id,
            'status' => Instructor::STATUS_ACTIVE,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $otherResult = YearlyResult::query()->create([
            'instructor_id' => $otherInstructor->id,
            'year' => 2026,
            'period_mode' => 'calendar_year',
            'status' => YearlyResult::STATUS_CALCULATED,
        ]);

        $this->actingAs($instructorUser)
            ->get('/lms/gv/standard-hours/my-results/'.$otherResult->id)
            ->assertForbidden();
    }

    public function test_instructor_can_view_and_submit_declaration(): void
    {
        [$instructorUser, $instructor] = $this->seedDemo();

        $this->actingAs($instructorUser)
            ->get('/lms/gv/standard-hours/my-results/declaration/edit?year=2027')
            ->assertOk()
            ->assertSee($instructor->code)
            ->assertSee('Truy xuất số giờ giảng từ lịch huấn luyện');

        $this->postJson('/lms/gv/standard-hours/my-results/declaration/retrieve-schedule', [
            'from_date' => '2027-01-01',
            'to_date' => '2027-12-31',
        ])->assertOk()->assertJsonStructure([
            'total_hours', 'total_periods', 'rows', 'by_subject', 'conversion_hours', 'research_hours',
        ]);
    }

    public function test_original_admin_shell_standard_hours_still_works(): void
    {
        [$instructorUser] = $this->seedDemo();

        $this->actingAs($instructorUser)
            ->get('/standard-hours')
            ->assertOk();

        $this->get('/standard-hours/my-results?year=2026')
            ->assertOk();

        $this->assertGreaterThan(0, ConversionRecord::query()->count());
    }

    public function test_instructor_can_create_and_submit_conversion_record_end_to_end(): void
    {
        [$instructorUser, $instructor] = $this->seedDemo();
        $category = ConversionCategory::query()->active()->manual()->firstOrFail();

        $response = $this->actingAs($instructorUser)
            ->post('/lms/gv/standard-hours/conversion-records', [
                'instructor_id' => $instructor->id,
                'conversion_category_id' => $category->id,
                'activity_name' => 'Biên soạn đề cương môn học',
                'activity_date' => '2027-03-01',
                'year' => 2027,
                'quantity' => 2,
                'status' => 'draft',
            ]);

        $record = ConversionRecord::query()->where('activity_name', 'Biên soạn đề cương môn học')->firstOrFail();
        $response->assertRedirect('/lms/gv/standard-hours/conversion-records/'.$record->id);
        $this->assertSame($instructor->id, $record->instructor_id);
        $this->assertSame(ConversionRecord::STATUS_DRAFT, $record->status);

        $this->get('/lms/gv/standard-hours/conversion-records/'.$record->id)
            ->assertOk()
            ->assertSee('Biên soạn đề cương môn học');

        $this->patch('/lms/gv/standard-hours/conversion-records/'.$record->id.'/submit')
            ->assertRedirect();

        $this->assertSame(ConversionRecord::STATUS_SUBMITTED, $record->fresh()->status);
    }

    public function test_instructor_cannot_view_another_instructors_conversion_record(): void
    {
        [$instructorUser, $instructor] = $this->seedDemo();

        $actor = User::query()->where('email', 'lms-sh-setup@example.test')->firstOrFail();
        $otherUnit = Unit::query()->where('code', '!=', $instructor->unit->code)->firstOrFail();
        $otherInstructor = Instructor::query()->create([
            'name' => 'GV khác 2',
            'code' => 'GV-OTHER-2',
            'email' => 'gv-other-2@example.test',
            'unit_id' => $otherUnit->id,
            'status' => Instructor::STATUS_ACTIVE,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $category = ConversionCategory::query()->active()->manual()->firstOrFail();
        $otherRecord = ConversionRecord::query()->create([
            'instructor_id' => $otherInstructor->id,
            'conversion_category_id' => $category->id,
            'activity_name' => 'Hoạt động của GV khác',
            'activity_date' => '2027-02-01',
            'year' => 2027,
            'period_mode' => 'calendar_year',
            'quantity' => 1,
            'converted_hours' => 1,
            'status' => ConversionRecord::STATUS_DRAFT,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->actingAs($instructorUser)
            ->get('/lms/gv/standard-hours/conversion-records/'.$otherRecord->id)
            ->assertForbidden();

        $this->get('/lms/gv/standard-hours/conversion-records/'.$otherRecord->id.'/edit')
            ->assertForbidden();
    }

    public function test_instructor_can_create_and_submit_research_record_end_to_end(): void
    {
        [$instructorUser, $instructor] = $this->seedDemo();
        $category = ResearchCategory::query()->active()->firstOrFail();

        $response = $this->actingAs($instructorUser)
            ->post('/lms/gv/standard-hours/research-records', [
                'instructor_id' => $instructor->id,
                'research_category_id' => $category->id,
                'product_name' => 'Bài báo khoa học kiểm thử LMS',
                'acceptance_date' => '2027-03-01',
                'member_count' => 1,
                'participation_type' => ResearchRecord::PARTICIPATION_LEAD,
                'duration_years' => 1,
                'status' => 'draft',
            ]);

        $record = ResearchRecord::query()->where('product_name', 'Bài báo khoa học kiểm thử LMS')->firstOrFail();
        $response->assertRedirect('/lms/gv/standard-hours/research-records/'.$record->id);
        $this->assertSame($instructor->id, $record->instructor_id);
        $this->assertSame(ResearchRecord::STATUS_DRAFT, $record->status);

        $this->get('/lms/gv/standard-hours/research-records/'.$record->id)
            ->assertOk()
            ->assertSee('Bài báo khoa học kiểm thử LMS');

        $this->patch('/lms/gv/standard-hours/research-records/'.$record->id.'/submit')
            ->assertRedirect();

        $this->assertSame(ResearchRecord::STATUS_SUBMITTED, $record->fresh()->status);
    }

    public function test_instructor_cannot_view_another_instructors_research_record(): void
    {
        [$instructorUser, $instructor] = $this->seedDemo();

        $actor = User::query()->where('email', 'lms-sh-setup@example.test')->firstOrFail();
        $otherUnit = Unit::query()->where('code', '!=', $instructor->unit->code)->firstOrFail();
        $otherInstructor = Instructor::query()->create([
            'name' => 'GV khác 3',
            'code' => 'GV-OTHER-3',
            'email' => 'gv-other-3@example.test',
            'unit_id' => $otherUnit->id,
            'status' => Instructor::STATUS_ACTIVE,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $category = ResearchCategory::query()->active()->firstOrFail();
        $otherRecord = ResearchRecord::query()->create([
            'instructor_id' => $otherInstructor->id,
            'research_category_id' => $category->id,
            'product_name' => 'Sản phẩm của GV khác',
            'acceptance_date' => '2027-02-01',
            'year' => 2027,
            'period_mode' => 'calendar_year',
            'member_count' => 1,
            'participation_type' => ResearchRecord::PARTICIPATION_LEAD,
            'duration_years' => 1,
            'annual_product_hours' => 1,
            'calculated_hours' => 1,
            'converted_hours' => 1,
            'status' => ResearchRecord::STATUS_DRAFT,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->actingAs($instructorUser)
            ->get('/lms/gv/standard-hours/research-records/'.$otherRecord->id)
            ->assertForbidden();

        $this->get('/lms/gv/standard-hours/research-records/'.$otherRecord->id.'/edit')
            ->assertForbidden();
    }

    public function test_instructor_can_create_and_submit_external_activity_end_to_end(): void
    {
        [$instructorUser, $instructor] = $this->seedDemo();

        $response = $this->actingAs($instructorUser)
            ->post('/lms/gv/standard-hours/external-activities', [
                'instructor_id' => $instructor->id,
                'activity_type' => ExternalActivityRecord::TYPE_ORGANIZATION,
                'activity_name' => 'Tổ chức hội thao cấp trường LMS',
                'from_date' => '2027-04-01',
            ]);

        $record = ExternalActivityRecord::query()->where('activity_name', 'Tổ chức hội thao cấp trường LMS')->firstOrFail();
        $response->assertRedirect('/lms/gv/standard-hours/external-activities/'.$record->id);
        $this->assertSame($instructor->id, $record->instructor_id);
        $this->assertSame(ExternalActivityRecord::STATUS_DRAFT, $record->status);

        $this->get('/lms/gv/standard-hours/external-activities/'.$record->id)
            ->assertOk()
            ->assertSee('Tổ chức hội thao cấp trường LMS');

        $this->patch('/lms/gv/standard-hours/external-activities/'.$record->id.'/submit')
            ->assertRedirect();

        $this->assertSame(ExternalActivityRecord::STATUS_SUBMITTED, $record->fresh()->status);
    }

    public function test_instructor_cannot_view_another_instructors_external_activity(): void
    {
        [$instructorUser, $instructor] = $this->seedDemo();

        $actor = User::query()->where('email', 'lms-sh-setup@example.test')->firstOrFail();
        $otherUnit = Unit::query()->where('code', '!=', $instructor->unit->code)->firstOrFail();
        $otherInstructor = Instructor::query()->create([
            'name' => 'GV khác 4',
            'code' => 'GV-OTHER-4',
            'email' => 'gv-other-4@example.test',
            'unit_id' => $otherUnit->id,
            'status' => Instructor::STATUS_ACTIVE,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $otherRecord = ExternalActivityRecord::query()->create([
            'instructor_id' => $otherInstructor->id,
            'activity_type' => ExternalActivityRecord::TYPE_OTHER,
            'activity_name' => 'Hoạt động của GV khác',
            'from_date' => '2027-02-01',
            'year' => 2027,
            'period_mode' => 'calendar_year',
            'status' => ExternalActivityRecord::STATUS_DRAFT,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->actingAs($instructorUser)
            ->get('/lms/gv/standard-hours/external-activities/'.$otherRecord->id)
            ->assertForbidden();

        $this->get('/lms/gv/standard-hours/external-activities/'.$otherRecord->id.'/edit')
            ->assertForbidden();
    }

    public function test_instructor_can_view_own_hour_exchange_balances(): void
    {
        [$instructorUser] = $this->seedDemo();

        $this->actingAs($instructorUser)
            ->get('/lms/gv/standard-hours/hour-exchanges')
            ->assertOk()
            ->assertSee('Quy đổi giờ NCKH');
    }
}
