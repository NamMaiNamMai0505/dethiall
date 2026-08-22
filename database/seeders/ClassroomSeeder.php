<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Classroom\Models\Classroom;

class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classrooms = [
            // H1
            ['name' => 'H1.101', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H1.102', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H1.103', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H1.201', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H1.202', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H1.203', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H1.205', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H1.206', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H1.207', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H1.208', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H1.209', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.201', 'building_id' => 1, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],

            // H2
            ['name' => 'H2.102', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.103', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.104', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.105', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.202', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.203', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.204', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.205', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.301', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.302', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.303', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.304', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.305', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H2.401', 'building_id' => 2, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],

            // H3
            ['name' => 'H3.101', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H3.102', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H3.103', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H3.104', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H3.105', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H3.106', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H3.201', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H3.202', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H3.203', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H3.204', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H3.205', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H3.206', 'building_id' => 3, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],

            // H4
            ['name' => 'H4.103', 'building_id' => 4, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H4.104', 'building_id' => 4, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H4.203', 'building_id' => 4, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H4.204', 'building_id' => 4, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H4.303', 'building_id' => 4, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H4.304', 'building_id' => 4, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H4.403', 'building_id' => 4, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H4.404', 'building_id' => 4, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H4.502', 'building_id' => 4, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H4.503', 'building_id' => 4, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],

            // H5
            ['name' => 'H5.201', 'building_id' => 5, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'H5.202', 'building_id' => 5, 'status' => true, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($classrooms as $classroom) {
            Classroom::create($classroom);
        }
    }
}
