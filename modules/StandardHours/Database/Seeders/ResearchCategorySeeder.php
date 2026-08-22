<?php

namespace Modules\StandardHours\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StandardHours\Models\ResearchCategory;

class ResearchCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Free unique `name` slots used by old sample categories.
        ResearchCategory::query()
            ->whereIn('code', [
                'NCKH-DT-CS',
                'NCKH-DT-BO',
                'NCKH-DT-QG',
                'NCKH-GT',
                'NCKH-BB-ISSN',
                'NCKH-SACH',
                'NCKH-HOITHAO',
            ])
            ->get()
            ->each(function (ResearchCategory $category) {
                $category->update([
                    'name' => $category->name.' [cũ-'.$category->code.']',
                    'is_active' => false,
                ]);
            });

        $categories = [
            [
                'code' => 'NCKH-01',
                'name' => 'Đề tài cấp cơ sở do Giám đốc hoặc Hiệu trưởng nhà trường phê duyệt',
                'unit' => 'Đề tài',
                'research_hours' => 1200,
            ],
            [
                'code' => 'NCKH-02',
                'name' => 'Đề tài cấp cơ sở do Thủ trưởng Bộ Tổng Tham mưu, Thủ trưởng Tổng cục Chính trị, Thủ trưởng Tổng cục hoặc Thủ trưởng đơn vị quản lý trường phê duyệt',
                'unit' => 'Đề tài',
                'research_hours' => 1800,
            ],
            [
                'code' => 'NCKH-03',
                'name' => 'Đề tài cấp Bộ',
                'unit' => 'Đề tài',
                'research_hours' => 2400,
            ],
            [
                'code' => 'NCKH-04',
                'name' => 'Đề tài cấp quốc gia',
                'unit' => 'Đề tài',
                'research_hours' => 3600,
            ],
            [
                'code' => 'NCKH-05',
                'name' => 'Sáng kiến cấp cơ sở do Giám đốc hoặc Hiệu trưởng nhà trường phê duyệt',
                'unit' => 'Sáng kiến',
                'research_hours' => 300,
            ],
            [
                'code' => 'NCKH-06',
                'name' => 'Sáng kiến cấp cơ sở do Thủ trưởng Bộ Tổng Tham mưu, Thủ trưởng Tổng cục Chính trị, Thủ trưởng Tổng cục hoặc Thủ trưởng đơn vị quản lý trường phê duyệt',
                'unit' => 'Sáng kiến',
                'research_hours' => 450,
            ],
            [
                'code' => 'NCKH-07',
                'name' => 'Sáng kiến cấp Bộ',
                'unit' => 'Sáng kiến',
                'research_hours' => 600,
            ],
            [
                'code' => 'NCKH-08',
                'name' => 'Sáng kiến cấp quốc gia',
                'unit' => 'Sáng kiến',
                'research_hours' => 900,
            ],
            [
                'code' => 'NCKH-09',
                'name' => 'Giáo trình, tài liệu dạy học, tài liệu huấn luyện, điều lệ, điều lệnh',
                'unit' => 'Giáo trình',
                'research_hours' => 1200,
            ],
            [
                'code' => 'NCKH-10',
                'name' => 'Bài báo khoa học được công bố trên tạp chí khoa học có mã số xuất bản (ISSN)',
                'unit' => 'Bài báo',
                'research_hours' => 300,
            ],
            [
                'code' => 'NCKH-11',
                'name' => 'Báo cáo khoa học tại hội thảo khoa học cấp cơ sở do Thủ trưởng Bộ Tổng Tham mưu, Thủ trưởng Tổng cục Chính trị, Thủ trưởng Tổng cục hoặc Thủ trưởng đơn vị quản lý trường phê duyệt',
                'unit' => 'Báo cáo',
                'research_hours' => 300,
            ],
            [
                'code' => 'NCKH-12',
                'name' => 'Báo cáo khoa học tại hội thảo khoa học cấp Bộ',
                'unit' => 'Báo cáo',
                'research_hours' => 450,
            ],
            [
                'code' => 'NCKH-13',
                'name' => 'Báo cáo khoa học tại hội thảo khoa học cấp quốc gia',
                'unit' => 'Báo cáo',
                'research_hours' => 600,
            ],
            [
                'code' => 'NCKH-14',
                'name' => 'Hướng dẫn học viên nghiên cứu đề tài khoa học cấp cơ sở do Giám đốc hoặc Hiệu trưởng nhà trường phê duyệt, được đánh giá xếp loại Xuất sắc',
                'unit' => 'Đề tài',
                'research_hours' => 75,
            ],
            [
                'code' => 'NCKH-15',
                'name' => 'Hướng dẫn học viên nghiên cứu đề tài khoa học cấp cơ sở do Giám đốc hoặc Hiệu trưởng nhà trường phê duyệt, được đánh giá xếp loại Đạt yêu cầu',
                'unit' => 'Đề tài',
                'research_hours' => 30,
            ],
            [
                'code' => 'NCKH-16',
                'name' => 'Thành viên hội đồng khoa học cấp cơ sở thông qua đề cương đề tài, sáng kiến, giáo trình hoặc tài liệu dạy học',
                'unit' => 'Đề cương',
                'research_hours' => 3,
            ],
            [
                'code' => 'NCKH-17',
                'name' => 'Thành viên hội đồng khoa học cấp cơ sở nghiệm thu đề tài, sáng kiến, giáo trình hoặc tài liệu dạy học',
                'unit' => 'Đề tài',
                'research_hours' => 6,
            ],
        ];

        foreach ($categories as $category) {
            ResearchCategory::updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'unit' => $category['unit'],
                    'research_hours' => $category['research_hours'],
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }

        // Deactivate old sample categories not in the official list.
        ResearchCategory::query()
            ->whereIn('code', [
                'NCKH-DT-CS',
                'NCKH-DT-BO',
                'NCKH-DT-QG',
                'NCKH-GT',
                'NCKH-BB-ISSN',
                'NCKH-SACH',
                'NCKH-HOITHAO',
            ])
            ->update(['is_active' => false]);
    }
}
