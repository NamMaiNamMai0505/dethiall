<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $extraUnits = [
            ['code' => 'BGH', 'name' => 'Ban Giám hiệu', 'abbreviation' => 'BGH', 'parent_code' => 'CDHC2', 'level' => 2],
            ['code' => 'PHONG_CT', 'name' => 'Phòng Chính trị', 'abbreviation' => 'PCT', 'parent_code' => 'CDHC2', 'level' => 2],
            ['code' => 'PHONG_HC', 'name' => 'Phòng Hậu cần', 'abbreviation' => 'PHC', 'parent_code' => 'CDHC2', 'level' => 2],
            ['code' => 'PHONG_KT', 'name' => 'Phòng Kỹ thuật', 'abbreviation' => 'PKT', 'parent_code' => 'CDHC2', 'level' => 2],
            ['code' => 'PHONG_TCKT', 'name' => 'Phòng Tài chính - Kế toán', 'abbreviation' => 'TCKT', 'parent_code' => 'CDHC2', 'level' => 2],
            ['code' => 'PHONG_KHCN', 'name' => 'Phòng Khoa học công nghệ', 'abbreviation' => 'KHCN', 'parent_code' => 'CDHC2', 'level' => 2],
            ['code' => 'BKHQS', 'name' => 'Ban Khoa học Quân sự', 'abbreviation' => 'KHQS', 'parent_code' => 'CDHC2', 'level' => 2, 'functional_type' => 'approval_agency'],
            ['code' => 'BKT&ĐBCLGDĐT', 'name' => 'Ban Khảo thí và Đảm bảo chất lượng giáo dục đào tạo', 'abbreviation' => 'KTĐBCL', 'parent_code' => 'CDHC2', 'level' => 2, 'functional_type' => 'approval_agency'],
            ['code' => 'BAN_QLHV', 'name' => 'Ban Quản lý học viên', 'abbreviation' => 'QLHV', 'parent_code' => 'CDHC2', 'level' => 2],
            ['code' => 'BAN_QUAN_LUC', 'name' => 'Ban Quân lực', 'abbreviation' => 'QL', 'parent_code' => 'PHONG_TMHC', 'level' => 3],
            ['code' => 'BAN_CAN_BO', 'name' => 'Ban Cán bộ', 'abbreviation' => 'CB', 'parent_code' => 'PHONG_CT', 'level' => 3],
            ['code' => 'BAN_TUYEN_HUAN', 'name' => 'Ban Tuyên huấn', 'abbreviation' => 'TH', 'parent_code' => 'PHONG_CT', 'level' => 3],
            ['code' => 'BAN_HANH_CHINH', 'name' => 'Ban Hành chính', 'abbreviation' => 'HC', 'parent_code' => 'PHONG_TMHC', 'level' => 3],
            ['code' => 'BAN_DOANH_TRAI', 'name' => 'Ban Doanh trại', 'abbreviation' => 'DT', 'parent_code' => 'PHONG_HC', 'level' => 3],
            ['code' => 'BAN_QUAN_Y', 'name' => 'Ban Quân y', 'abbreviation' => 'QY', 'parent_code' => 'PHONG_HC', 'level' => 3],
            ['code' => 'BAN_XE_MAY', 'name' => 'Ban Xe máy', 'abbreviation' => 'XM', 'parent_code' => 'PHONG_KT', 'level' => 3],
            ['code' => 'BAN_VU_KHI', 'name' => 'Ban Vũ khí', 'abbreviation' => 'VK', 'parent_code' => 'PHONG_KT', 'level' => 3],
            ['code' => 'TTVIEN', 'name' => 'Thư viện', 'abbreviation' => 'TV', 'parent_code' => 'CDHC2', 'level' => 2],
            ['code' => 'TTTHNN', 'name' => 'Trung tâm Tin học - Ngoại ngữ', 'abbreviation' => 'THNN', 'parent_code' => 'CDHC2', 'level' => 2],
            ['code' => 'TTTH', 'name' => 'Tổ Thực hành', 'abbreviation' => 'TTH', 'parent_code' => 'PHONG_DT', 'level' => 3],
            ['code' => 'DD1_D1', 'name' => 'Đại đội 1', 'abbreviation' => 'C1', 'parent_code' => 'D1', 'level' => 3],
            ['code' => 'DD2_D1', 'name' => 'Đại đội 2', 'abbreviation' => 'C2', 'parent_code' => 'D1', 'level' => 3],
            ['code' => 'DD3_D1', 'name' => 'Đại đội 3', 'abbreviation' => 'C3', 'parent_code' => 'D1', 'level' => 3],
            ['code' => 'DD4_D1', 'name' => 'Đại đội 4', 'abbreviation' => 'C4', 'parent_code' => 'D1', 'level' => 3],
            ['code' => 'DD5_D2', 'name' => 'Đại đội 5', 'abbreviation' => 'C5', 'parent_code' => 'D2', 'level' => 3],
            ['code' => 'DD6_D2', 'name' => 'Đại đội 6', 'abbreviation' => 'C6', 'parent_code' => 'D2', 'level' => 3],
            ['code' => 'DD7_D2', 'name' => 'Đại đội 7', 'abbreviation' => 'C7', 'parent_code' => 'D2', 'level' => 3],
            ['code' => 'DD8_D2', 'name' => 'Đại đội 8', 'abbreviation' => 'C8', 'parent_code' => 'D2', 'level' => 3],
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

        $parentIds = DB::table('units')
            ->whereIn('code', collect($extraUnits)->pluck('parent_code')->unique()->all())
            ->pluck('id', 'code');

        $hasAbbreviation = Schema::hasColumn('units', 'abbreviation');
        $hasFunctionalType = Schema::hasColumn('units', 'functional_type');
        $hasFacultyCode = Schema::hasColumn('units', 'faculty_code');

        $extraRows = collect($extraUnits)
            ->map(function (array $unit) use ($parentIds, $hasAbbreviation, $hasFunctionalType, $hasFacultyCode): array {
                $row = [
                    'code' => $unit['code'],
                    'name' => $unit['name'],
                    'parent_id' => $parentIds[$unit['parent_code']] ?? 1,
                    'level' => $unit['level'],
                    'status' => 'active',
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($hasAbbreviation) {
                    $row['abbreviation'] = $unit['abbreviation'] ?? null;
                }

                if ($hasFunctionalType) {
                    $row['functional_type'] = $unit['functional_type'] ?? 'other';
                }

                if ($hasFacultyCode) {
                    $row['faculty_code'] = $unit['faculty_code'] ?? null;
                }

                return $row;
            })
            ->all();

        DB::table('units')->upsert(
            $extraRows,
            ['code'],
            array_values(array_filter([
                'name',
                $hasAbbreviation ? 'abbreviation' : null,
                'parent_id',
                'level',
                $hasFunctionalType ? 'functional_type' : null,
                $hasFacultyCode ? 'faculty_code' : null,
                'status',
                'updated_by',
                'updated_at',
            ]))
        );
    }
}
