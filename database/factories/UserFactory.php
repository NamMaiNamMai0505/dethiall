<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Instructor\Models\Instructor;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'role_id' => 2,
            'user_type' => 'internal_user',
            'status' => 1,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an instructor.
     */
    public function instructor(): static
    {
        return $this->state(function (array $attributes) {
            // Find an instructor without a user account, or create a new one
            $instructor = Instructor::factory()->create();

            return [
                'instructor_id' => $instructor->id,
                'user_type' => 'instructor',
                'unit_id' => $instructor->unit_id,
                'name' => $instructor->name,
                'email' => $instructor->email,
            ];
        });
    }

    /**
     * Indicate that the user is a student.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'student',
            'class_id' => \Modules\Class\Models\ClassModel::factory(),
        ]);
    }

    /**
     * Indicate that the user is an internal user.
     */
    public function internalUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'internal_user',
        ]);
    }
}
