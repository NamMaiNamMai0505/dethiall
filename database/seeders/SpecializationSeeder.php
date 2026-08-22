<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        $systemIds = DB::table('training_systems')
            ->whereIn('code', ['civilian', 'military'])
            ->pluck('id', 'code');

        $specializations = [
            ['code' => 'A.6720100', 'major_code' => '6720100', 'name' => 'Nhân viên quân y đại đội', 'system' => 'military', 'level' => 'beginner', 'months' => 6, 'form' => 'formal', 'certificate' => 'certificate'],
            ['code' => 'B.6720201', 'major_code' => '6720201', 'name' => 'Dược', 'system' => 'civilian', 'level' => 'advanced', 'months' => 36, 'form' => 'formal', 'certificate' => 'college_diploma'],
            ['code' => 'A.5720101', 'major_code' => '5720101', 'name' => 'Y sỹ đa khoa', 'system' => 'military', 'level' => 'intermediate', 'months' => 30, 'form' => 'formal', 'certificate' => 'secondary_diploma'],
            ['code' => 'A.5810207', 'major_code' => '5810207', 'name' => 'Kỹ thuật chế biến món ăn', 'system' => 'military', 'level' => 'intermediate', 'months' => 24, 'form' => 'formal', 'certificate' => 'secondary_diploma'],
            ['code' => 'B.6720101', 'major_code' => '6720101', 'name' => 'Y sỹ đa khoa', 'system' => 'civilian', 'level' => 'advanced', 'months' => 36, 'form' => 'formal', 'certificate' => 'college_diploma'],
            ['code' => 'B.6720301', 'major_code' => '6720301', 'name' => 'Điều dưỡng', 'system' => 'civilian', 'level' => 'advanced', 'months' => 36, 'form' => 'formal', 'certificate' => 'college_diploma'],
            ['code' => 'A.5340202', 'major_code' => '5340202', 'name' => 'Tài chính – Ngân hàng', 'system' => 'military', 'level' => 'intermediate', 'months' => 24, 'form' => 'formal', 'certificate' => 'secondary_diploma'],
            ['code' => 'A.5810208', 'major_code' => '5810207', 'name' => 'Kỹ thuật chế biến món ăn', 'system' => 'military', 'level' => 'intermediate', 'months' => 12, 'form' => 'conversion', 'certificate' => 'secondary_diploma'],
            ['code' => 'A.6720302', 'major_code' => '6720301', 'name' => 'Điều dưỡng', 'system' => 'military', 'level' => 'advanced', 'months' => 36, 'form' => 'bridging', 'certificate' => 'college_diploma'],
            ['code' => 'A.6720101', 'major_code' => '6720101', 'name' => 'Y sỹ đa khoa', 'system' => 'military', 'level' => 'advanced', 'months' => 36, 'form' => 'formal', 'certificate' => 'college_diploma'],
            ['code' => 'A.6720301', 'major_code' => '6720301', 'name' => 'Điều dưỡng', 'system' => 'military', 'level' => 'advanced', 'months' => 36, 'form' => 'formal', 'certificate' => 'college_diploma'],
        ];

        foreach ($specializations as $item) {
            $payload = [
                'training_system_id' => $systemIds[$item['system']],
                'major_code' => $item['major_code'],
                'name' => $item['name'],
                'level' => $item['level'],
                'duration_months' => $item['months'],
                'training_form' => $item['form'],
                'certification_type' => $item['certificate'],
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => now(),
            ];
            $existingId = DB::table('specializations')->where('code', $item['code'])->value('id');

            if ($existingId) {
                DB::table('specializations')->where('id', $existingId)->update($payload);
            } else {
                DB::table('specializations')->insert($payload + [
                    'code' => $item['code'],
                    'description' => null,
                    'prerequisites' => null,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
