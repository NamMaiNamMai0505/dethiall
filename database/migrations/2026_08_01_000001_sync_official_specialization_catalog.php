<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Danh mục theo tài liệu "DANH MỤC NGÀNH ĐÀO TẠO.docx" cập nhật 01/08/2026.
     *
     * Thứ tự chín dòng đầu bám theo chín bản ghi của danh mục cũ để giữ ID
     * đang được lớp, môn học, lịch và giảng viên tham chiếu.
     *
     * @var list<array<string, mixed>>
     */
    private const CATALOG = [
        [
            'legacy_codes' => ['NVQYC', '6720101.1'],
            'code' => 'A.6720100',
            'major_code' => '6720100',
            'name' => 'Nhân viên quân y đại đội',
            'system' => 'military',
            'level' => 'beginner',
            'duration_months' => 6,
            'training_form' => 'formal',
            'certification_type' => 'certificate',
        ],
        [
            'legacy_codes' => ['CDD', '6720201'],
            'code' => 'B.6720201',
            'major_code' => '6720201',
            'name' => 'Dược',
            'system' => 'civilian',
            'level' => 'advanced',
            'duration_months' => 36,
            'training_form' => 'formal',
            'certification_type' => 'college_diploma',
        ],
        [
            'legacy_codes' => ['TCYS', '5720101'],
            'code' => 'A.5720101',
            'major_code' => '5720101',
            'name' => 'Y sỹ đa khoa',
            'system' => 'military',
            'level' => 'intermediate',
            'duration_months' => 30,
            'training_form' => 'formal',
            'certification_type' => 'secondary_diploma',
        ],
        [
            'legacy_codes' => ['KTCBMA', '5810207'],
            'code' => 'A.5810207',
            'major_code' => '5810207',
            'name' => 'Kỹ thuật chế biến món ăn',
            'system' => 'military',
            'level' => 'intermediate',
            'duration_months' => 24,
            'training_form' => 'formal',
            'certification_type' => 'secondary_diploma',
        ],
        [
            'legacy_codes' => ['CDYS', '6720101'],
            'code' => 'B.6720101',
            'major_code' => '6720101',
            'name' => 'Y sỹ đa khoa',
            'system' => 'civilian',
            'level' => 'advanced',
            'duration_months' => 36,
            'training_form' => 'formal',
            'certification_type' => 'college_diploma',
        ],
        [
            'legacy_codes' => ['CDDD', '6720301'],
            'code' => 'B.6720301',
            'major_code' => '6720301',
            'name' => 'Điều dưỡng',
            'system' => 'civilian',
            'level' => 'advanced',
            'duration_months' => 36,
            'training_form' => 'formal',
            'certification_type' => 'college_diploma',
        ],
        [
            'legacy_codes' => ['5340202'],
            'code' => 'A.5340202',
            'major_code' => '5340202',
            'name' => 'Tài chính – Ngân hàng',
            'system' => 'military',
            'level' => 'intermediate',
            'duration_months' => 24,
            'training_form' => 'formal',
            'certification_type' => 'secondary_diploma',
        ],
        [
            'legacy_codes' => ['5810207.1'],
            'code' => 'A.5810208',
            'major_code' => '5810207',
            'name' => 'Kỹ thuật chế biến món ăn',
            'system' => 'military',
            'level' => 'intermediate',
            'duration_months' => 12,
            'training_form' => 'conversion',
            'certification_type' => 'secondary_diploma',
        ],
        [
            'legacy_codes' => ['6720301.1'],
            'code' => 'A.6720302',
            'major_code' => '6720301',
            'name' => 'Điều dưỡng',
            'system' => 'military',
            'level' => 'advanced',
            'duration_months' => 36,
            'training_form' => 'bridging',
            'certification_type' => 'college_diploma',
        ],
        [
            'legacy_codes' => [],
            'code' => 'A.6720101',
            'major_code' => '6720101',
            'name' => 'Y sỹ đa khoa',
            'system' => 'military',
            'level' => 'advanced',
            'duration_months' => 36,
            'training_form' => 'formal',
            'certification_type' => 'college_diploma',
        ],
        [
            'legacy_codes' => [],
            'code' => 'A.6720301',
            'major_code' => '6720301',
            'name' => 'Điều dưỡng',
            'system' => 'military',
            'level' => 'advanced',
            'duration_months' => 36,
            'training_form' => 'formal',
            'certification_type' => 'college_diploma',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('specializations') || ! Schema::hasTable('training_systems')) {
            return;
        }

        $addMajorCode = ! Schema::hasColumn('specializations', 'major_code');
        $addTrainingForm = ! Schema::hasColumn('specializations', 'training_form');
        if ($addMajorCode || $addTrainingForm) {
            Schema::table('specializations', function (Blueprint $table) use ($addMajorCode, $addTrainingForm) {
                if ($addMajorCode) {
                    $table->string('major_code', 50)->nullable()->after('code')->index();
                }
                if ($addTrainingForm) {
                    $table->string('training_form', 40)->nullable()->after('duration_months')->index();
                }
            });
        }

        $now = now();
        foreach ([
            'civilian' => [
                'name' => 'Hệ Dân sự',
                'description' => 'Chương trình đào tạo hệ dân sự',
                'is_active' => true,
                'sort_order' => 1,
            ],
            'military' => [
                'name' => 'Hệ Quân sự',
                'description' => 'Chương trình đào tạo hệ quân sự',
                'is_active' => true,
                'sort_order' => 2,
            ],
        ] as $code => $system) {
            $existingId = DB::table('training_systems')->where('code', $code)->value('id');
            if ($existingId) {
                DB::table('training_systems')->where('id', $existingId)->update($system + [
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('training_systems')->insert($system + [
                    'code' => $code,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $systemIds = DB::table('training_systems')
            ->whereIn('code', ['civilian', 'military'])
            ->pluck('id', 'code');

        foreach (self::CATALOG as $item) {
            $payload = [
                'training_system_id' => $systemIds[$item['system']],
                'code' => $item['code'],
                'major_code' => $item['major_code'],
                'name' => $item['name'],
                'level' => $item['level'],
                'duration_months' => $item['duration_months'],
                'training_form' => $item['training_form'],
                'certification_type' => $item['certification_type'],
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
            ];

            $official = DB::table('specializations')->where('code', $item['code'])->first();
            $legacyRows = empty($item['legacy_codes'])
                ? collect()
                : DB::table('specializations')->whereIn('code', $item['legacy_codes'])->get();

            if ($official) {
                $canonicalId = (int) $official->id;
            } elseif ($legacyRows->isNotEmpty()) {
                $canonicalId = (int) $legacyRows->shift()->id;
            } else {
                $canonicalId = (int) DB::table('specializations')->insertGetId($payload + [
                    'description' => null,
                    'prerequisites' => null,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                ]);
            }

            foreach ($legacyRows as $legacy) {
                if ((int) $legacy->id === $canonicalId) {
                    continue;
                }
                $this->moveReferences((int) $legacy->id, $canonicalId);
                DB::table('specializations')->where('id', $legacy->id)->update([
                    'is_active' => false,
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('specializations')->where('id', $canonicalId)->update($payload);
        }
    }

    public function down(): void
    {
        // Danh mục và hai cột mới có thể đã được dữ liệu nghiệp vụ tham chiếu.
        // Không tự động xóa/đổi ngược dữ liệu khi rollback code.
    }

    private function moveReferences(int $fromId, int $toId): void
    {
        foreach (['classes', 'instructors', 'subjects', 'training_schedules'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'specialization_id')) {
                DB::table($table)
                    ->where('specialization_id', $fromId)
                    ->update(['specialization_id' => $toId]);
            }
        }
    }
};
