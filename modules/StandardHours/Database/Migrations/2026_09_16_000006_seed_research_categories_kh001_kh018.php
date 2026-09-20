<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('research_categories')) {
            return;
        }

        $now = now();
        $rows = [
            ['code' => 'KH001', 'name' => 'Đề tài cấp cơ sở do Giám đốc, Hiệu trưởng nhà trường phê duyệt (K1.Đ16)', 'unit' => 'Đề tài', 'research_hours' => 1200],
            ['code' => 'KH002', 'name' => 'Đề tài cấp cơ sở do Thủ trưởng Bộ Tổng Tham mưu, Thủ trưởng Tổng cục Chính trị, Thủ trưởng tổng cục, Thủ trưởng đơn vị quản lý trường phê duyệt (K1.Đ16)', 'unit' => 'Đề tài', 'research_hours' => 1800],
            ['code' => 'KH003', 'name' => 'Đề tài cấp Bộ Quốc phòng (K1.Đ16)', 'unit' => 'Đề tài', 'research_hours' => 2400],
            ['code' => 'KH004', 'name' => 'Đề tài cấp Quốc gia (K1.Đ16)', 'unit' => 'Đề tài', 'research_hours' => 3600],
            ['code' => 'KH005', 'name' => 'Sáng kiến cấp cơ sở do Giám đốc, Hiệu trưởng nhà trường phê duyệt (K2.Đ16)', 'unit' => 'Sáng kiến', 'research_hours' => 300],
            ['code' => 'KH006', 'name' => 'Sáng kiến cấp cơ sở do Thủ trưởng Bộ Tổng Tham mưu, Thủ trưởng Tổng cục Chính trị, Thủ trưởng tổng cục, Thủ trưởng đơn vị quản lý trường phê duyệt (K2.Đ16)', 'unit' => 'Sáng kiến', 'research_hours' => 450],
            ['code' => 'KH007', 'name' => 'Sáng kiến cấp Bộ Quốc phòng (K2.Đ16)', 'unit' => 'Sáng kiến', 'research_hours' => 600],
            ['code' => 'KH008', 'name' => 'Sáng kiến cấp Quốc gia (K2.Đ16)', 'unit' => 'Sáng kiến', 'research_hours' => 900],
            ['code' => 'KH009', 'name' => 'Giáo trình, tài liệu dạy học, tài liệu huấn luyện, điều lệ, điều lệnh (K3.Đ16)', 'unit' => 'Giáo trình', 'research_hours' => 1200],
            ['code' => 'KH010', 'name' => 'Bài báo khoa học được công bố trên tạp chí khoa học có mã số xuất bản (ISSN) (K4.Đ16)', 'unit' => 'Bài báo', 'research_hours' => 300],
            ['code' => 'KH011', 'name' => 'Báo cáo khoa học tại hội thảo khoa học cấp cơ sở do Thủ trưởng Bộ Tổng Tham mưu, Thủ trưởng Tổng cục Chính trị, Thủ trưởng tổng cục, Thủ trưởng đơn vị quản lý trường phê duyệt (K4.Đ16)', 'unit' => 'Báo cáo', 'research_hours' => 300],
            ['code' => 'KH012', 'name' => 'Báo cáo khoa học tại hội thảo khoa học cấp Bộ Quốc phòng (K4.Đ16)', 'unit' => 'Báo cáo', 'research_hours' => 450],
            ['code' => 'KH013', 'name' => 'Báo cáo khoa học tại hội thảo khoa học cấp Quốc gia (K4.Đ16)', 'unit' => 'Báo cáo', 'research_hours' => 600],
            ['code' => 'KH014', 'name' => 'Báo cáo khoa học tại hội thảo khoa học cấp Quốc tế (K4.Đ16)', 'unit' => 'Báo cáo', 'research_hours' => 600],
            ['code' => 'KH015', 'name' => 'Hướng dẫn học viên nghiên cứu 01 đề tài khoa học cấp cơ sở do Giám đốc, Hiệu trưởng nhà trường phê duyệt được đánh giá xếp loại: Xuất sắc (K5.Đ16)', 'unit' => 'Đề tài', 'research_hours' => 75],
            ['code' => 'KH016', 'name' => 'Hướng dẫn học viên nghiên cứu 01 đề tài khoa học cấp cơ sở do Giám đốc, Hiệu trưởng nhà trường phê duyệt được đánh giá xếp loại: Đạt yêu cầu (K5.Đ16)', 'unit' => 'Đề tài', 'research_hours' => 30],
            ['code' => 'KH017', 'name' => 'Thành viên hội đồng khoa học cấp cơ sở (do Giám đốc, Hiệu trưởng nhà trường phê duyệt) thông qua 01 đề cương đề tài, sáng kiến, giáo trình, tài liệu dạy học (K6.Đ16)', 'unit' => 'Đề cương', 'research_hours' => 3],
            ['code' => 'KH018', 'name' => 'Thành viên hội đồng khoa học cấp cơ sở (do Giám đốc, Hiệu trưởng nhà trường phê duyệt) nghiệm thu 01 đề tài, sáng kiến, giáo trình, tài liệu dạy học (K6.Đ16)', 'unit' => 'Đề tài', 'research_hours' => 6],
        ];

        foreach ($rows as $row) {
            DB::table('research_categories')->updateOrInsert(
                ['code' => $row['code']],
                $row + ['is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('research_categories')) {
            return;
        }

        DB::table('research_categories')
            ->whereIn('code', array_map(fn (int $number): string => 'KH'.str_pad((string) $number, 3, '0', STR_PAD_LEFT), range(1, 18)))
            ->delete();
    }
};
