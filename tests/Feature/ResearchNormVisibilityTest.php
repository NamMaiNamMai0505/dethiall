<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\ObjectType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ResearchNormVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_sees_their_research_norm_on_list_and_declaration_form(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $objectType = ObjectType::query()->create([
            'code' => 'NCKH-DEMO',
            'name' => 'Đối tượng kiểm thử NCKH',
            'standard_hours' => 380,
            'research_hours' => 80,
            'is_active' => true,
        ]);
        $instructor = Instructor::factory()->create([
            'object_type_id' => $objectType->id,
        ]);
        $user = User::factory()->create([
            'name' => $instructor->name,
            'user_type' => 'instructor',
            'instructor_id' => $instructor->id,
            'unit_id' => $instructor->unit_id,
        ]);

        // Quyền tổng của phân hệ không còn mở được ứng dụng con — phải cấp đúng
        // quyền Xem/Thêm của ứng dụng "Kê khai NCKH".
        foreach ([
            'standard-hours.index',
            'standard-hours.research-records.view',
            'standard-hours.research-records.create',
        ] as $permissionName) {
            $user->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        }

        $this->actingAs($user)
            ->get(route('standard-hours.research-records.index'))
            ->assertOk()
            ->assertSee('Định mức NCKH phải thực hiện')
            ->assertSee('80,00')
            ->assertSee('NCKH-DEMO');

        $this->get(route('standard-hours.research-records.create'))
            ->assertOk()
            ->assertSee('Định mức NCKH phải thực hiện')
            ->assertSee('researchNorms', false)
            ->assertSee('type="hidden" id="instructor_id"', false)
            ->assertSee('80,00');
    }
}
