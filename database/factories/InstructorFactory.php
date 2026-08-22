<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Instructor\Models\Instructor;

/**
 * @extends Factory<Instructor>
 */
class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    public function definition(): array
    {
        $code = 'GV-T-'.strtoupper(substr(uniqid(), -6));
        // schema NOT NULL created_by/updated_by
        $actor = User::factory()->create([
            'user_type' => 'internal_user',
            'instructor_id' => null,
            'status' => 1,
            'email' => 'actor-'.$code.'@test.local',
        ]);

        return [
            'name' => fake()->name(),
            'code' => $code,
            'email' => strtolower($code).'@test.local',
            'phone' => fake()->optional()->numerify('09########'),
            'status' => Instructor::STATUS_ACTIVE,
            'description' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => Instructor::STATUS_INACTIVE]);
    }
}
