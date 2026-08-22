<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'id' => 1,
                'code' => 'CDHC2',
                'name' => 'Trường Cao đẳng Hậu cần 2',
                'parent_id' => null,
                'level' => 1,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'code' => 'PHONG_DT',
                'name' => 'Phòng Đào tạo',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'code' => 'PHONG_TMHC',
                'name' => 'Phòng Tham mưu - Hành chính',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'code' => 'TOCNTT',
                'name' => 'Tổ Công nghệ thông tin',
                'parent_id' => 3,
                'level' => 3,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'code' => 'K2',
                'name' => 'Khoa Khoa học xã hội và nhân văn',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'code' => 'K9',
                'name' => 'Khoa Hậu cần chung',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'code' => 'K1',
                'name' => 'Khoa Quân sự chung',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'code' => 'K3',
                'name' => 'Khoa Khoa học cơ bản',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'code' => 'K8',
                'name' => 'Khoa Dược',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'code' => 'K7',
                'name' => 'Khoa Điều dưỡng',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'code' => 'K6',
                'name' => 'Khoa Y học quân sự',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'code' => 'K5',
                'name' => 'Khoa Y học lâm sàng',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 13,
                'code' => 'K4',
                'name' => 'Khoa Y học cơ sở',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 14,
                'code' => 'D1',
                'name' => 'Tiểu đoàn 1',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 15,
                'code' => 'D2',
                'name' => 'Tiểu đoàn 2',
                'parent_id' => 1,
                'level' => 2,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Migration chuẩn hóa K1–K8 có thể tạo các đơn vị placeholder trước khi
        // seeder chạy trên database sạch. Đổi mã tạm để không va unique(code),
        // sau đó upsert theo ID giúp seeder chạy lặp an toàn.
        $hasCanonicalRoot = DB::table('units')
            ->where('id', 1)
            ->where('code', 'CDHC2')
            ->exists();
        if (! $hasCanonicalRoot) {
            DB::table('units')
                ->whereBetween('id', [1, 8])
                ->whereIn('code', ['K1', 'K2', 'K3', 'K4', 'K5', 'K6', 'K7', 'K8'])
                ->get(['id'])
                ->each(function ($unit): void {
                    DB::table('units')->where('id', $unit->id)->update([
                        'code' => '__UNIT_SEED_'.$unit->id,
                    ]);
                });
        }

        DB::table('units')->upsert(
            $units,
            ['id'],
            [
                'code', 'name', 'parent_id', 'level', 'status',
                'created_by', 'updated_by', 'updated_at',
            ]
        );
    }
}
