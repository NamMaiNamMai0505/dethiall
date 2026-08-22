<?php

namespace Modules\StandardHours\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StandardHours\Models\ObjectType;

/**
 * 3 đối tượng gộp định mức GC (Đ.11) + NCKH (Đ.13) — không gắn chức danh.
 * Giờ phải đạt = standard_hours × position.ratio_percent.
 */
class ObjectTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => '01',
                'name' => 'Đối tượng 01',
                'description' => 'Học viện / Trường sĩ quan / Đại học — GC 280 · NCKH 600 giờ HC',
                'standard_hours' => 280,
                'research_hours' => 600,
            ],
            [
                'code' => '02',
                'name' => 'Đối tượng 02',
                'description' => 'Cao đẳng (CDHC2) — GC 380 · NCKH 300 giờ HC',
                'standard_hours' => 380,
                'research_hours' => 300,
            ],
            [
                'code' => '03',
                'name' => 'Đối tượng 03',
                'description' => 'Trung cấp / Trường quân sự — GC 430 · NCKH 150 giờ HC',
                'standard_hours' => 430,
                'research_hours' => 150,
            ],
        ];

        foreach ($types as $row) {
            ObjectType::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'standard_hours' => $row['standard_hours'],
                    'research_hours' => $row['research_hours'],
                    'is_active' => true,
                ]
            );
        }
    }
}
