<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            // Trung cấp y sĩ, id = 3
            [
                'id' => 1,
                'name' => 'Lớp Y53',
                'code' => 'Y53',
                'specialization_id' => 3, 
                'instructor_id' => 1,
                'start_date' => Carbon::now()->addWeeks(2), //01/09/2023
                'end_date' => Carbon::now()->addWeeks(2)->addMonths(30), //30/06/2026
                'duration_months' => 3,
                'management_unit' => 'Đại đội 4, Tiểu Đoàn 2',
                'classroom_id' => 1,
                'max_students' => 30,
                'current_students' => 30,
                'is_active' => true,
                'description' => 'Lớp học y sĩ đa khoa',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Lớp K1',
                'code' => 'K1',
                'specialization_id' => 4, // ngành đào tạo kĩ thuật nấu ăn
                'instructor_id' => 1,
                'start_date' => Carbon::now()->addWeeks(2),
                'end_date' => Carbon::now()->addWeeks(2)->addMonths(3),
                'duration_months' => 3,
                'management_unit' => 'Đại đội 5, Tiểu Đoàn 2',
                'classroom_id' => 1,
                'max_students' => 30,
                'current_students' => 30,
                'is_active' => true,
                'description' => 'Lớp học y sĩ đa khoa',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('classes')->insert($classes);
    }
}
