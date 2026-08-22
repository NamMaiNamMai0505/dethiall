<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run seeders in the correct order based on foreign key dependencies
        $this->call([
            UserSeeder::class,           // First: Create users (referenced by other tables)
            UnitSeeder::class,           // Second: Create units
            SpecializationSeeder::class, // Third: Create specializations
            SubjectSeeder::class,        // Fourth: Create subjects (depends on specializations and users)
            InstructorSeeder::class,     // Fifth: Create instructors (depends on specializations and users)
            BuildingSeeder::class,      // Sixth: Create buildings
            ClassroomSeeder::class,      // Seventh: Create classrooms
            ClassSeeder::class,          // Eighth: Create classes (depends on specializations, instructors, classrooms, and users)
            RolePermissionSeeder::class,  // Nine: Create all roles and permissions
            DigitalSignatureSeeder::class, // Mẫu chữ ký LHL HK2 + claim theo tên
            // Optional demos (chạy riêng khi cần):
            // php artisan db:seed --class=ScheduleDemoSeeder  // lịch HT + LMS GV/HV
            // php artisan db:seed --class=TrainingScheduleExportDemoSeeder // test gom tiết 2–3, 3–4
            // php artisan db:seed --class=LmsDemoSeeder       // khóa LMS đủ feature
        ]);

        $this->command->info('All seeders have been run successfully!');
    }
}
