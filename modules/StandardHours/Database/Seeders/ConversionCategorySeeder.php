<?php

namespace Modules\StandardHours\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StandardHours\Models\ConversionCategory;

class ConversionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'HD-01',
                'name' => 'Tiết giảng, hướng dẫn tập bài, thực hành, thí nghiệm ban ngày tại giảng đường',
                'unit' => 'Tiết',
                'coefficient' => 1.0,
            ],
            [
                'code' => 'HD-02',
                'name' => 'Tiết giảng, hướng dẫn tập bài, thực hành, thí nghiệm ban ngày tại thao trường, bãi tập, xưởng thực hành, nhà máy, bệnh viện,...',
                'unit' => 'Tiết',
                'coefficient' => 1.2,
            ],
            [
                'code' => 'HD-03',
                'name' => 'Tiết giảng, hướng dẫn tập bài, thực hành, thí nghiệm ban đêm tại giảng đường',
                'unit' => 'Tiết',
                'coefficient' => 1.2,
            ],
            [
                'code' => 'HD-04',
                'name' => 'Tiết giảng, hướng dẫn tập bài, thực hành, thí nghiệm ban đêm tại thao trường, bãi tập, xưởng thực hành, nhà máy, bệnh viện',
                'unit' => 'Tiết',
                'coefficient' => 1.5,
            ],
            [
                'code' => 'HD-05',
                'name' => 'Giảng dạy đối tượng thạc sĩ',
                'unit' => 'Tiết',
                'coefficient' => 1.5,
            ],
            [
                'code' => 'HD-06',
                'name' => 'Giảng dạy đối tượng tiến sĩ',
                'unit' => 'Tiết',
                'coefficient' => 2.0,
            ],
            [
                'code' => 'HD-07',
                'name' => 'Giảng dạy bằng tiếng nước ngoài (không phải môn ngoại ngữ)',
                'unit' => 'Tiết',
                'coefficient' => 2.0,
            ],
            [
                'code' => 'HD-08',
                'name' => 'Giảng dạy trong môi trường đặc thù quân sự',
                'unit' => 'Tiết',
                'coefficient' => 2.0,
            ],
            [
                'code' => 'HD-09',
                'name' => 'Hướng dẫn đồ án, khóa luận tốt nghiệp',
                'unit' => 'Khóa luận',
                'coefficient' => 25,
            ],
            [
                'code' => 'HD-10',
                'name' => 'Hướng dẫn luận văn thạc sĩ',
                'unit' => 'Luận văn',
                'coefficient' => 70,
            ],
            [
                'code' => 'HD-11',
                'name' => 'Hướng dẫn luận văn thạc sĩ (người hướng dẫn thứ nhất)',
                'unit' => 'Luận văn',
                'coefficient' => 40,
            ],
            [
                'code' => 'HD-12',
                'name' => 'Hướng dẫn luận văn thạc sĩ (người hướng dẫn thứ hai)',
                'unit' => 'Luận văn',
                'coefficient' => 30,
            ],
            [
                'code' => 'HD-13',
                'name' => 'Hướng dẫn luận án tiến sĩ',
                'unit' => 'Luận án',
                'coefficient' => 200,
            ],
            [
                'code' => 'HD-14',
                'name' => 'Hướng dẫn luận án tiến sĩ (người hướng dẫn thứ nhất)',
                'unit' => 'Luận án',
                'coefficient' => 120,
            ],
            [
                'code' => 'HD-15',
                'name' => 'Hướng dẫn luận án tiến sĩ (người hướng dẫn thứ hai)',
                'unit' => 'Luận án',
                'coefficient' => 80,
            ],
            [
                'code' => 'HD-16',
                'name' => 'Biên soạn đề thi trắc nghiệm học phần',
                'unit' => 'Bộ',
                'coefficient' => 2,
            ],
            [
                'code' => 'HD-17',
                'name' => 'Biên soạn đề thi tự luận học phần',
                'unit' => 'Bộ',
                'coefficient' => 1.5,
            ],
            [
                'code' => 'HD-18',
                'name' => 'Biên soạn đề thi thực hành học phần',
                'unit' => 'Bộ',
                'coefficient' => 1,
            ],
            [
                'code' => 'HD-19',
                'name' => 'Biên soạn đề thi vấn đáp học phần',
                'unit' => 'Bộ',
                'coefficient' => 0.75,
            ],
            [
                'code' => 'HD-20',
                'name' => 'Coi thi học phần',
                'unit' => 'Tiết',
                'coefficient' => 0.3,
            ],
            [
                'code' => 'HD-21',
                'name' => 'Chấm bài trắc nghiệm học phần',
                'unit' => 'Bài',
                'coefficient' => 0.1,
            ],
            [
                'code' => 'HD-22',
                'name' => 'Chấm bài tự luận học phần',
                'unit' => 'Bài',
                'coefficient' => 0.2,
            ],
            [
                'code' => 'HD-23',
                'name' => 'Chấm thi vấn đáp/thực hành trong giảng đường',
                'unit' => 'Tiết',
                'coefficient' => 0.5,
            ],
            [
                'code' => 'HD-24',
                'name' => 'Chấm thi vấn đáp/thực hành ngoài thao trường hoặc xưởng thực hành',
                'unit' => 'Tiết',
                'coefficient' => 0.75,
            ],
            [
                'code' => 'HD-25',
                'name' => 'Biên soạn đề thi trắc nghiệm tốt nghiệp',
                'unit' => 'Bộ',
                'coefficient' => 2.5,
            ],
            // Item 26 in source text was corrupted ("227"); use 2.0 consistent with scale vs học phần.
            [
                'code' => 'HD-26',
                'name' => 'Biên soạn đề thi tự luận tốt nghiệp',
                'unit' => 'Bộ',
                'coefficient' => 2.0,
            ],
            [
                'code' => 'HD-27',
                'name' => 'Biên soạn đề thi thực hành tốt nghiệp',
                'unit' => 'Bộ',
                'coefficient' => 1.5,
            ],
            [
                'code' => 'HD-28',
                'name' => 'Biên soạn đề thi vấn đáp tốt nghiệp',
                'unit' => 'Bộ',
                'coefficient' => 1.25,
            ],
            [
                'code' => 'HD-29',
                'name' => 'Coi thi tốt nghiệp',
                'unit' => 'Tiết',
                'coefficient' => 0.5,
            ],
            [
                'code' => 'HD-30',
                'name' => 'Chấm bài trắc nghiệm tốt nghiệp',
                'unit' => 'Bài',
                'coefficient' => 0.2,
            ],
            [
                'code' => 'HD-31',
                'name' => 'Chấm bài tự luận tốt nghiệp',
                'unit' => 'Bài',
                'coefficient' => 0.4,
            ],
            [
                'code' => 'HD-32',
                'name' => 'Chấm thi vấn đáp/thực hành tốt nghiệp trong giảng đường',
                'unit' => 'Bài',
                'coefficient' => 1,
            ],
            [
                'code' => 'HD-33',
                'name' => 'Chấm thi ngoài thao trường hoặc xưởng thực hành trong kỳ thi tốt nghiệp',
                'unit' => 'Tiết',
                'coefficient' => 1.2,
            ],
            [
                'code' => 'HD-34',
                'name' => 'Chấm đồ án, khóa luận tốt nghiệp',
                'unit' => 'Đồ án',
                'coefficient' => 1.5,
            ],
            [
                'code' => 'HD-35',
                'name' => 'Thành viên hội đồng đánh giá luận văn thạc sĩ',
                'unit' => 'Hội đồng',
                'coefficient' => 3,
            ],
            [
                'code' => 'HD-36',
                'name' => 'Thành viên hội đồng đánh giá luận án tiến sĩ',
                'unit' => 'Hội đồng',
                'coefficient' => 6,
            ],
            [
                'code' => 'HD-37',
                'name' => 'Khác',
                'unit' => 'Tùy chỉnh',
                'coefficient' => 1.0,
                'description' => 'Danh mục tùy chỉnh — điều chỉnh hệ số khi kê khai nếu cần',
            ],
        ];

        foreach ($categories as $index => $category) {
            ConversionCategory::updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'unit' => $category['unit'],
                    'conversion_method' => ConversionCategory::METHOD_COEFFICIENT,
                    'coefficient' => $category['coefficient'],
                    'fixed_hours' => null,
                    'description' => $category['description'] ?? null,
                    'entry_source' => in_array($category['code'], ['HD-01', 'HD-02', 'HD-03', 'HD-04', 'HD-05', 'HD-06', 'HD-07', 'HD-08'], true)
                        ? 'schedule'
                        : 'manual',
                    'is_active' => true,
                ]
            );
        }

        // Deactivate old sample categories not in the official list (keep history if referenced).
        ConversionCategory::query()
            ->whereIn('code', ['HD-GD-TIET', 'HD-HOI-THAO', 'HD-BIEN-SOAN'])
            ->update(['is_active' => false]);
    }
}
