<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Instructor\Models\Instructor;
use Spatie\Permission\Models\Role;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructors = [
            [
                'id' => 1,
                'name' => 'Nguyễn Văn A',
                'code' => 'GV-0001',
                'email' => 'nguyenvanminh@hc2.vn',
                'phone' => '0987654321',
                'specialization_id' => 1,
                'unit_id' => 10, // khoa điều dưỡng
                'status' => 'active',
                'description' => 'Giảng viên khoa điều dưỡng',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Trần Văn B',
                'code' => 'GV-0002',
                'email' => 'tranvanb@hc2.vn',
                'phone' => '0987654321',
                'specialization_id' => 1,
                'unit_id' => 10, // khoa điều dưỡng
                'status' => 'active',
                'description' => 'Giảng viên nấu ăn',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('instructors')->insert($instructors);

        // Optionally create user accounts for these instructors
        // You can uncomment this code if you want to create login accounts for seed instructors
        /*
        $instructorRole = Role::where('name', 'instructor')->first() ?? Role::where('name', 'user')->first();

        foreach ($instructors as $instructorData) {
            $instructor = Instructor::find($instructorData['id']);

            if ($instructor && !$instructor->hasUserAccount()) {
                $user = User::create([
                    'name' => $instructor->name,
                    'code' => $instructor->code,
                    'email' => $instructor->email,
                    'password' => bcrypt('password'), // Default password
                    'instructor_id' => $instructor->id,
                    'unit_id' => $instructor->unit_id,
                    'user_type' => 'instructor',
                    'status' => 1,
                ]);

                if ($instructorRole) {
                    $user->syncRoles([$instructorRole->name]);
                    $user->update(['role_id' => $instructorRole->id]);
                }

                echo "Created user account for instructor: {$instructor->name}\n";
            }
        }
        */
    }
}
