<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('it@Cdhc2'),
            'email_verified_at' => now(),
            'role_id' => 1,
        ]);

        // Create sample users
        User::create([
            'name' => 'Hoàng Kim Tuyến',
            'email' => 'hoangtuyenblogger@gmail.com',
            'password' => Hash::make('it@Cdhc2'),
            'email_verified_at' => now(),
            'role_id' => 1,
        ]);

        // Create more users using factory
        // User::factory(10)->create();
    }
}
