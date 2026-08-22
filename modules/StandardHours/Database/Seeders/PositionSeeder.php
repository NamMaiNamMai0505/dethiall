<?php

namespace Modules\StandardHours\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StandardHours\Models\Position;

/**
 * Chức danh — mỗi chức danh 1 dòng riêng (không gộp tên), dù cùng tỉ lệ %.
 * ratio_percent = % của định mức Đối tượng (Đ.11); min_classroom_percent = tối thiểu giờ dạy trực tiếp (Đ.12.2).
 *
 * VD: Hiệu trưởng 10% và Giám đốc 10% là 2 bản ghi riêng.
 */
class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            // —— 10% ——
            ['name' => 'Giám đốc', 'ratio_percent' => 10, 'min_classroom_percent' => 50],
            ['name' => 'Hiệu trưởng', 'ratio_percent' => 10, 'min_classroom_percent' => 50],
            ['name' => 'Chính ủy', 'ratio_percent' => 10, 'min_classroom_percent' => 50],

            // —— 15% ——
            ['name' => 'Phó Giám đốc', 'ratio_percent' => 15, 'min_classroom_percent' => 50],
            ['name' => 'Phó Hiệu trưởng', 'ratio_percent' => 15, 'min_classroom_percent' => 50],
            ['name' => 'Phó Chính ủy', 'ratio_percent' => 15, 'min_classroom_percent' => 50],

            // —— 20% ——
            ['name' => 'Trưởng phòng', 'ratio_percent' => 20, 'min_classroom_percent' => 50],
            ['name' => 'Tương đương Trưởng phòng', 'ratio_percent' => 20, 'min_classroom_percent' => 50],

            // —— 25% ——
            ['name' => 'Phó Trưởng phòng', 'ratio_percent' => 25, 'min_classroom_percent' => 50],
            ['name' => 'Trưởng ban trực thuộc nhà trường', 'ratio_percent' => 25, 'min_classroom_percent' => 50],

            // —— Khoa ——
            // TT 06/2026/BQP nội bộ: Chủ nhiệm/Trưởng khoa = 60% định mức đối tượng.
            ['name' => 'Chủ nhiệm khoa', 'ratio_percent' => 60, 'min_classroom_percent' => 50],
            ['name' => 'Trưởng khoa', 'ratio_percent' => 60, 'min_classroom_percent' => 50],
            ['name' => 'Chủ nhiệm khoa (Bí thư ĐU/CB)', 'ratio_percent' => 45, 'min_classroom_percent' => 50],
            ['name' => 'Chủ nhiệm khoa (Phó Bí thư ĐU/CB)', 'ratio_percent' => 50, 'min_classroom_percent' => 50],

            ['name' => 'Phó Chủ nhiệm khoa', 'ratio_percent' => 70, 'min_classroom_percent' => 50],
            ['name' => 'Phó Trưởng khoa', 'ratio_percent' => 70, 'min_classroom_percent' => 50],
            ['name' => 'Phó Chủ nhiệm khoa (Bí thư ĐU/CB)', 'ratio_percent' => 55, 'min_classroom_percent' => 50],
            ['name' => 'Phó Chủ nhiệm khoa (Phó Bí thư ĐU/CB)', 'ratio_percent' => 60, 'min_classroom_percent' => 50],

            // —— Bộ môn ——
            ['name' => 'Chủ nhiệm bộ môn', 'ratio_percent' => 80, 'min_classroom_percent' => 50],
            ['name' => 'Trưởng bộ môn', 'ratio_percent' => 80, 'min_classroom_percent' => 50],
            ['name' => 'Trưởng bộ môn (Bí thư chi bộ)', 'ratio_percent' => 65, 'min_classroom_percent' => 50],
            ['name' => 'Trưởng bộ môn (Phó Bí thư chi bộ)', 'ratio_percent' => 70, 'min_classroom_percent' => 50],

            ['name' => 'Phó Chủ nhiệm bộ môn', 'ratio_percent' => 85, 'min_classroom_percent' => 50],
            ['name' => 'Phó Trưởng bộ môn', 'ratio_percent' => 85, 'min_classroom_percent' => 50],
            ['name' => 'Phó Trưởng bộ môn (Bí thư chi bộ)', 'ratio_percent' => 70, 'min_classroom_percent' => 50],
            ['name' => 'Phó Trưởng bộ môn (Phó Bí thư chi bộ)', 'ratio_percent' => 75, 'min_classroom_percent' => 50],

            // —— Không giữ chức vụ ——
            ['name' => 'Giảng viên', 'ratio_percent' => 100, 'min_classroom_percent' => 50],
        ];

        $keepNames = [];

        foreach ($positions as $position) {
            $keepNames[] = $position['name'];
            Position::withTrashed()->updateOrCreate(
                ['name' => $position['name']],
                [
                    'ratio_percent' => $position['ratio_percent'],
                    'min_classroom_percent' => $position['min_classroom_percent'],
                    'is_active' => true,
                    'deleted_at' => null,
                ]
            );
        }

        // Tắt bản ghi gộp tên cũ (A / B / C, "và tương đương"…) — soft, giữ FK
        Position::query()
            ->whereNotIn('name', $keepNames)
            ->where(function ($q) {
                $q->where('name', 'like', '%/%')
                    ->orWhere('name', 'like', '%và tương đương%');
            })
            ->update(['is_active' => false]);
    }
}
