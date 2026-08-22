<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Specialization\Models\Specialization;
use Modules\Specialization\Models\TrainingSystem;
use Modules\Subject\Support\SubjectCodePrefix;
use Modules\TrainingSchedule\Services\TrainingScheduleService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SpecializationOfficialCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_official_catalog_is_loaded_with_training_system_level_and_duration(): void
    {
        $expected = [
            'B.6720101' => ['6720101', 'Y sỹ đa khoa', 'civilian', 'advanced', 36, 'formal'],
            'B.6720201' => ['6720201', 'Dược', 'civilian', 'advanced', 36, 'formal'],
            'B.6720301' => ['6720301', 'Điều dưỡng', 'civilian', 'advanced', 36, 'formal'],
            'A.6720101' => ['6720101', 'Y sỹ đa khoa', 'military', 'advanced', 36, 'formal'],
            'A.6720301' => ['6720301', 'Điều dưỡng', 'military', 'advanced', 36, 'formal'],
            'A.6720302' => ['6720301', 'Điều dưỡng', 'military', 'advanced', 36, 'bridging'],
            'A.5810207' => ['5810207', 'Kỹ thuật chế biến món ăn', 'military', 'intermediate', 24, 'formal'],
            'A.5810208' => ['5810207', 'Kỹ thuật chế biến món ăn', 'military', 'intermediate', 12, 'conversion'],
            'A.5720101' => ['5720101', 'Y sỹ đa khoa', 'military', 'intermediate', 30, 'formal'],
            'A.5340202' => ['5340202', 'Tài chính – Ngân hàng', 'military', 'intermediate', 24, 'formal'],
            'A.6720100' => ['6720100', 'Nhân viên quân y đại đội', 'military', 'beginner', 6, 'formal'],
        ];

        $catalog = Specialization::query()
            ->with('trainingSystem')
            ->whereIn('code', array_keys($expected))
            ->get()
            ->keyBy('code');

        $this->assertCount(11, $catalog);
        foreach ($expected as $code => [$majorCode, $name, $system, $level, $months, $form]) {
            $specialization = $catalog->get($code);
            $this->assertNotNull($specialization, "Thiếu ngành {$code}");
            $this->assertSame($majorCode, $specialization->major_code);
            $this->assertSame($name, $specialization->name);
            $this->assertSame($system, $specialization->trainingSystem?->code);
            $this->assertSame($level, $specialization->level);
            $this->assertSame($months, $specialization->duration_months);
            $this->assertSame($form, $specialization->training_form);
            $this->assertTrue($specialization->is_active);
        }
    }

    public function test_official_code_with_a_dot_can_be_saved_and_is_visible_on_the_catalog_page(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('specializations.index', 'web'),
            Permission::findOrCreate('specializations.edit', 'web'),
        ]);
        $specialization = Specialization::query()->where('code', 'A.6720100')->firstOrFail();

        $this->actingAs($user)
            ->put(route('specializations.update', $specialization), [
                'training_system_id' => $specialization->training_system_id,
                'name' => $specialization->name,
                'code' => $specialization->code,
                'major_code' => $specialization->major_code,
                'description' => $specialization->description,
                'level' => $specialization->level,
                'duration_months' => $specialization->duration_months,
                'training_form' => $specialization->training_form,
                'certification_type' => $specialization->certification_type,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('specializations.show', $specialization));

        $this->actingAs($user)
            ->get(route('specializations.index', ['sort_by' => 'code', 'sort_order' => 'asc']))
            ->assertOk()
            ->assertSee('Hệ đào tạo')
            ->assertSee('A.6720100')
            ->assertSee('6720100')
            ->assertSee('Hệ Quân sự');
    }

    public function test_same_name_is_allowed_in_different_systems_and_training_forms(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('specializations.edit', 'web'));
        $military = Specialization::query()->where('code', 'A.6720101')->firstOrFail();
        $bridging = Specialization::query()->where('code', 'A.6720302')->firstOrFail();

        $this->actingAs($user)
            ->put(route('specializations.update', $military), [
                'training_system_id' => $military->training_system_id,
                'name' => 'Y sỹ đa khoa',
                'code' => $military->code,
                'major_code' => $military->major_code,
                'description' => $military->description,
                'level' => $military->level,
                'duration_months' => $military->duration_months,
                'training_form' => $military->training_form,
                'certification_type' => $military->certification_type,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('specializations.show', $military));

        $this->actingAs($user)
            ->put(route('specializations.update', $bridging), [
                'training_system_id' => $bridging->training_system_id,
                'name' => 'Điều dưỡng',
                'code' => $bridging->code,
                'major_code' => $bridging->major_code,
                'description' => $bridging->description,
                'level' => $bridging->level,
                'duration_months' => $bridging->duration_months,
                'training_form' => $bridging->training_form,
                'certification_type' => $bridging->certification_type,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('specializations.show', $bridging));
    }

    public function test_subject_prefix_uses_the_unique_program_number(): void
    {
        $formal = Specialization::query()->where('code', 'A.6720301')->firstOrFail();
        $bridging = Specialization::query()->where('code', 'A.6720302')->firstOrFail();

        $this->assertSame('A_6720301', SubjectCodePrefix::suggest($formal));
        $this->assertSame('A_6720302', SubjectCodePrefix::suggest($bridging));
        $this->assertSame(
            'A_6720302_M009K2',
            SubjectCodePrefix::buildSubjectCode(SubjectCodePrefix::suggest($bridging), 'M009K2')
        );
        $this->assertSame('6720301', app(TrainingScheduleService::class)->getSpecializationCode($bridging));
    }

    public function test_internal_number_is_auto_generated_while_major_code_drives_ui_and_export(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('specializations.create', 'web'));
        $civilianSystemId = TrainingSystem::query()
            ->where('code', 'civilian')
            ->value('id');

        $this->actingAs($user)
            ->post(route('specializations.store'), [
                'training_system_id' => $civilianSystemId,
                'name' => 'Chương trình kiểm thử mã tự động',
                'major_code' => '9999999',
                'level' => 'advanced',
                'duration_months' => 36,
                'training_form' => 'formal',
                'certification_type' => 'college_diploma',
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $created = Specialization::query()
            ->where('major_code', '9999999')
            ->firstOrFail();
        $this->assertStringStartsWith('CTDT-', $created->code);
        $this->assertStringContainsString('9999999', $created->selection_label);
        $this->assertStringNotContainsString($created->code, $created->selection_label);

        $csv = $this->actingAs($user)
            ->get(route('specializations.export', ['training_system_id' => $civilianSystemId]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Mã ngành', $csv);
        $this->assertStringContainsString('9999999', $csv);
        $this->assertStringNotContainsString('Mã số', $csv);
        $this->assertStringNotContainsString($created->code, $csv);
    }
}
