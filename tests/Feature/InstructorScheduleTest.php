<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Instructor\Models\Instructor;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InstructorScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructorUser;

    protected Instructor $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('instructor-schedule.index', 'web');
        $instructorRole = Role::findOrCreate('instructor', 'web');
        $instructorRole->syncPermissions(['instructor-schedule.index']);

        $this->instructor = Instructor::factory()->create();

        $this->instructorUser = User::factory()->create([
            'user_type' => 'instructor',
            'instructor_id' => $this->instructor->id,
            'status' => 1,
        ]);
        $this->instructorUser->assignRole($instructorRole);
    }

    public function test_non_authenticated_user_cannot_access_instructor_schedule(): void
    {
        $response = $this->get(route('instructor-schedule.index'));

        $response->assertRedirect('/login');
    }

    public function test_non_instructor_user_gets_403(): void
    {
        $regularUser = User::factory()->create([
            'user_type' => 'internal_user',
            'instructor_id' => null,
            'status' => 1,
        ]);

        $response = $this->actingAs($regularUser)->get(route('instructor-schedule.index'));

        $response->assertStatus(403);
    }

    public function test_instructor_can_access_calendar_view(): void
    {
        $response = $this->actingAs($this->instructorUser)->get(route('instructor-schedule.index'));

        $response->assertStatus(200);
        $response->assertViewIs('instructorschedule::index');
        $response->assertViewHas('calendar');
        $response->assertViewHas('stats');
        $response->assertViewHas('instructor');
        $response->assertViewHas('dateRangeLabel');
    }

    public function test_calendar_displays_current_week_by_default(): void
    {
        $response = $this->actingAs($this->instructorUser)->get(route('instructor-schedule.index'));

        $response->assertStatus(200);

        $calendar = $response->viewData('calendar');

        // Should have 7 days (Monday to Sunday)
        $this->assertCount(7, $calendar);

        // First day should be Monday
        $firstDay = Carbon::parse(array_key_first($calendar));
        $this->assertEquals(Carbon::MONDAY, $firstDay->dayOfWeek);

        // Last day should be Sunday
        $lastDay = Carbon::parse(array_key_last($calendar));
        $this->assertEquals(Carbon::SUNDAY, $lastDay->dayOfWeek);
    }

    public function test_instructor_only_sees_their_own_schedules(): void
    {
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $mySchedule = ScheduleDetail::factory()->create([
            'instructor_id' => $this->instructor->id,
            'date' => $monday->toDateString(),
            'period' => 1,
        ]);

        $otherInstructor = Instructor::factory()->create();
        ScheduleDetail::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'date' => $monday->toDateString(),
            'period' => 2,
        ]);

        $response = $this->actingAs($this->instructorUser)->get(route('instructor-schedule.index'));

        $response->assertStatus(200);

        $calendar = $response->viewData('calendar');
        $mondayData = $calendar[$monday->toDateString()];

        $this->assertNotNull($mondayData['periods'][1]);
        $this->assertEquals($mySchedule->id, $mondayData['periods'][1]->id);

        $this->assertNull($mondayData['periods'][2]);
    }

    public function test_week_navigation_works(): void
    {
        $response = $this->actingAs($this->instructorUser)
            ->get(route('instructor-schedule.index', ['week_offset' => 1]));

        $response->assertStatus(200);
        $calendar = $response->viewData('calendar');

        $firstDay = Carbon::parse(array_key_first($calendar));
        $expectedMonday = Carbon::now()->addWeek()->startOfWeek(Carbon::MONDAY);

        $this->assertEquals($expectedMonday->toDateString(), $firstDay->toDateString());

        $response = $this->actingAs($this->instructorUser)
            ->get(route('instructor-schedule.index', ['week_offset' => -1]));

        $response->assertStatus(200);
        $calendar = $response->viewData('calendar');

        $firstDay = Carbon::parse(array_key_first($calendar));
        $expectedMonday = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY);

        $this->assertEquals($expectedMonday->toDateString(), $firstDay->toDateString());
    }

    public function test_statistics_calculated_for_displayed_week(): void
    {
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        ScheduleDetail::factory()->create([
            'instructor_id' => $this->instructor->id,
            'lesson_type' => 'theory',
            'date' => $monday->toDateString(),
            'period' => 1,
        ]);

        ScheduleDetail::factory()->create([
            'instructor_id' => $this->instructor->id,
            'lesson_type' => 'practice',
            'date' => $monday->copy()->addDay()->toDateString(),
            'period' => 2,
        ]);

        ScheduleDetail::factory()->create([
            'instructor_id' => $this->instructor->id,
            'lesson_type' => 'theory',
            'date' => $monday->copy()->addWeek()->toDateString(),
            'period' => 1,
        ]);

        $response = $this->actingAs($this->instructorUser)->get(route('instructor-schedule.index'));

        $response->assertStatus(200);
        $stats = $response->viewData('stats');

        $this->assertEquals(2, $stats['total_hours']);
        $this->assertEquals(1, $stats['theory_hours']);
        $this->assertEquals(1, $stats['practice_hours']);
    }

    public function test_statistics_tab_uses_the_same_custom_date_range_and_all_lesson_types(): void
    {
        $from = Carbon::today()->subDays(10);
        $to = $from->copy()->addDays(3);

        foreach ([
            ['type' => 'theory', 'date' => $from->toDateString(), 'period' => 1],
            ['type' => 'practice', 'date' => $from->toDateString(), 'period' => 2],
            ['type' => 'self_study', 'date' => $from->copy()->addDay()->toDateString(), 'period' => 6],
            ['type' => 'final_exam', 'date' => $from->copy()->addDays(2)->toDateString(), 'period' => 7],
        ] as $item) {
            ScheduleDetail::factory()->create([
                'instructor_id' => $this->instructor->id,
                'lesson_type' => $item['type'],
                'date' => $item['date'],
                'period' => $item['period'],
            ]);
        }

        ScheduleDetail::factory()->create([
            'instructor_id' => $this->instructor->id,
            'lesson_type' => 'theory',
            'date' => $to->copy()->addDay()->toDateString(),
            'period' => 1,
        ]);

        $response = $this->actingAs($this->instructorUser)->get(route('instructor-schedule.index', [
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'tab' => 'statistics',
        ]));

        $response
            ->assertOk()
            ->assertViewHas('activeTab', 'statistics')
            ->assertViewHas('isCustomRange', true)
            ->assertSee('data-active-tab="statistics"', false)
            ->assertSee('Thống kê số tiết');

        $stats = $response->viewData('stats');

        $this->assertSame(4, $stats['total_hours']);
        $this->assertSame(1, $stats['theory_hours']);
        $this->assertSame(1, $stats['practice_hours']);
        $this->assertSame(1, $stats['self_study_hours']);
        $this->assertSame(1, $stats['exam_hours']);
        $this->assertSame(2, $stats['morning_hours']);
        $this->assertSame(2, $stats['afternoon_hours']);
        $this->assertSame(3, $stats['teaching_days']);
        $this->assertCount(4, $stats['type_breakdown']);
        $this->assertCount(3, $stats['daily_breakdown']);
    }

    public function test_custom_date_filter_requires_a_complete_valid_range(): void
    {
        $this->actingAs($this->instructorUser)
            ->get(route('instructor-schedule.index', [
                'date_from' => Carbon::today()->toDateString(),
            ]))
            ->assertSessionHasErrors('date_to');

        $this->actingAs($this->instructorUser)
            ->get(route('instructor-schedule.index', [
                'date_from' => Carbon::today()->toDateString(),
                'date_to' => Carbon::yesterday()->toDateString(),
            ]))
            ->assertSessionHasErrors('date_to');
    }

    public function test_today_is_highlighted(): void
    {
        $response = $this->actingAs($this->instructorUser)->get(route('instructor-schedule.index'));

        $response->assertStatus(200);
        $calendar = $response->viewData('calendar');

        $today = Carbon::today()->toDateString();

        $todayFound = false;
        foreach ($calendar as $date => $dayData) {
            if ($dayData['is_today']) {
                $this->assertEquals($today, $date);
                $todayFound = true;
                break;
            }
        }

        $this->assertTrue($todayFound, 'Today should be marked in the calendar');
    }

    public function test_calendar_has_9_periods_for_each_day(): void
    {
        $response = $this->actingAs($this->instructorUser)->get(route('instructor-schedule.index'));

        $response->assertStatus(200);
        $calendar = $response->viewData('calendar');

        foreach ($calendar as $dayData) {
            $this->assertArrayHasKey('periods', $dayData);
            $this->assertCount(9, $dayData['periods']);

            for ($i = 1; $i <= 9; $i++) {
                $this->assertArrayHasKey($i, $dayData['periods']);
            }
        }
    }
}
