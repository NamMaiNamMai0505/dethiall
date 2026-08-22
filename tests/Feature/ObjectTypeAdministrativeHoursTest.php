<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Requests\StoreObjectTypeRequest;
use Tests\TestCase;

class ObjectTypeAdministrativeHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_adds_and_backfills_administrative_hours(): void
    {
        $this->assertTrue(Schema::hasColumn('standard_object_types', 'administrative_hours'));

        foreach (['01' => 840, '02' => 1140, '03' => 1290] as $code => $hours) {
            $this->assertDatabaseHas('standard_object_types', [
                'code' => $code,
                'administrative_hours' => $hours,
            ]);
        }
    }

    public function test_object_type_accepts_and_casts_administrative_hours(): void
    {
        $objectType = ObjectType::query()->create([
            'code' => '99',
            'name' => 'Đối tượng kiểm thử giờ hành chính',
            'standard_hours' => 500,
            'research_hours' => 250,
            'administrative_hours' => 1500,
            'is_active' => true,
        ]);

        $this->assertSame('1500.00', $objectType->fresh()->administrative_hours);
    }

    public function test_create_validation_requires_administrative_hours(): void
    {
        $request = new StoreObjectTypeRequest;
        $data = [
            'code' => '98',
            'name' => 'Đối tượng validation',
            'standard_hours' => 400,
            'research_hours' => 200,
            'is_active' => true,
        ];

        $this->assertTrue(Validator::make($data, $request->rules())->fails());

        $data['administrative_hours'] = 1200;
        $this->assertFalse(Validator::make($data, $request->rules())->fails());
    }

    public function test_index_places_administrative_hours_next_to_research_hours(): void
    {
        $view = file_get_contents(
            base_path('modules/StandardHours/Views/object-types/index.blade.php')
        );

        $researchColumn = strpos($view, '>NCKH<');
        $administrativeColumn = strpos($view, '>Giờ hành chính<');

        $this->assertNotFalse($researchColumn);
        $this->assertNotFalse($administrativeColumn);
        $this->assertGreaterThan($researchColumn, $administrativeColumn);
    }
}
