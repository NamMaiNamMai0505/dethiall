<?php

namespace Modules\StandardHours\Database\Seeders;

use App\Models\MilitaryRank;
use App\Models\User;
use App\Support\ApplicationRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\AcademicDepartment;
use Modules\StandardHours\Models\CalculationLog;
use Modules\StandardHours\Models\ConversionCategory;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Models\DepartmentOvertimePool;
use Modules\StandardHours\Models\HourExchangeRecord;
use Modules\StandardHours\Models\InstructorNormReduction;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Models\Position;
use Modules\StandardHours\Models\ResearchCategory;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Models\ResearchRecordMember;
use Modules\StandardHours\Models\YearlyResult;
use Modules\StandardHours\Services\CalculationService;
use Modules\StandardHours\Services\PeriodService;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Bộ demo end-to-end cho /standard-hours.
 *
 * Giảng viên: giangvien@example.com / password
 * Người duyệt: quanlykhoa@example.com / password
 */
class StandardHoursDemoSeeder extends Seeder
{
    public const YEAR = '2026';

    public const INSTRUCTOR_EMAIL = 'giangvien@example.com';

    public const REVIEWER_EMAIL = 'quanlykhoa@example.com';

    public const DEMO_MARKER = '[DEMO GIỜ CHUẨN]';

    public function run(): void
    {
        $this->call([
            ObjectTypeSeeder::class,
            PositionSeeder::class,
            ConversionCategorySeeder::class,
            ResearchCategorySeeder::class,
            StandardHoursSettingsSeeder::class,
        ]);

        $instructor = Instructor::query()->where('code', 'GV-0001')->first();
        if (! $instructor || ! $instructor->unit_id) {
            $this->command?->error('Không tìm thấy giảng viên demo GV-0001 có đơn vị quản lý.');

            return;
        }

        $this->ensureRolePermissions();
        [$instructorUser, $reviewer] = $this->ensureDemoUsers($instructor);
        $this->configureInstructor($instructor, $instructorUser, $reviewer);

        $conversionEvidence = $this->writeEvidence(
            'standard-hours/demo/minh-chung-hoat-dong-chuyen-mon.docx',
            'MINH CHỨNG HOẠT ĐỘNG CHUYÊN MÔN',
            [
                'Giảng viên: Nguyễn Văn A (GV-0001)',
                'Năm: '.self::YEAR,
                'Nội dung: Hướng dẫn khóa luận, biên soạn đề và chấm thi.',
                'Tài liệu này được tạo tự động để kiểm thử quy trình kê khai và phê duyệt.',
            ]
        );
        $researchEvidence = $this->writeEvidence(
            'standard-hours/demo/minh-chung-nghien-cuu-khoa-hoc.docx',
            'MINH CHỨNG NGHIÊN CỨU KHOA HỌC',
            [
                'Giảng viên: Nguyễn Văn A (GV-0001)',
                'Năm: '.self::YEAR,
                'Nội dung: Bài báo khoa học, sáng kiến và hướng dẫn học viên NCKH.',
                'Tài liệu này được tạo tự động để kiểm thử quy trình kê khai và phê duyệt.',
            ]
        );

        DB::transaction(function () use (
            $instructor,
            $instructorUser,
            $reviewer,
            $conversionEvidence,
            $researchEvidence
        ): void {
            $this->seedConversionRecords(
                $instructor,
                $instructorUser,
                $reviewer,
                $conversionEvidence
            );
            $this->seedResearchRecords(
                $instructor,
                $instructorUser,
                $reviewer,
                $researchEvidence
            );
            $this->seedNormReductionAndExchange($instructor, $reviewer);
            $this->seedCalculatedResult($instructor, $reviewer);
            $this->seedDepartmentOvertime($instructor, $reviewer);
        });

        $this->command?->info('✓ Đã tạo bộ demo đầy đủ cho /standard-hours');
        $this->command?->line('  Giảng viên: '.self::INSTRUCTOR_EMAIL.' / password');
        $this->command?->line('  Người duyệt: '.self::REVIEWER_EMAIL.' / password');
        $this->command?->line('  Giảng viên: Nguyễn Văn A (GV-0001) · '.$instructor->unit?->name);
        $this->command?->line('  Năm: '.self::YEAR);
        $this->command?->line('  HDSD trên web: /standard-hours/guide');
    }

    private function ensureRolePermissions(): void
    {
        // Quyền tổng chỉ còn ý nghĩa "vào được phân hệ"; mỗi ứng dụng phải được
        // cấp quyền riêng theo ApplicationRegistry.
        $selfServiceApplications = [
            'standard-hours.declarations',
            'standard-hours.conversion-records',
            'standard-hours.research-records',
            'standard-hours.external-activities',
        ];

        $permissionNames = [
            'standard-hours.index',
            'standard-hours.show',
            'standard-hours.create',
            'standard-hours.edit',
            'standard-hours.delete',
        ];

        foreach ($selfServiceApplications as $application) {
            foreach ([
                ApplicationRegistry::ACTION_VIEW,
                ApplicationRegistry::ACTION_CREATE,
                ApplicationRegistry::ACTION_EDIT,
                ApplicationRegistry::ACTION_DELETE,
            ] as $action) {
                $permissionNames = array_merge(
                    $permissionNames,
                    ApplicationRegistry::permissionNamesFor($application, $action)
                );
            }
        }
        $permissionNames = array_values(array_unique($permissionNames));

        // Người thẩm định trong bộ demo quản lý trọn phân hệ Giờ chuẩn.
        $reviewerPermissionNames = array_values(array_unique(array_merge(
            $permissionNames,
            ['standard-hours.approve'],
            ApplicationRegistry::subsystemPermissionNames('standard-hours')
        )));
        foreach ($reviewerPermissionNames as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate('instructor', 'web')->givePermissionTo($permissionNames);
        Role::findOrCreate('manager', 'web')->givePermissionTo($reviewerPermissionNames);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array{User,User}
     */
    private function ensureDemoUsers(Instructor $instructor): array
    {
        $instructorRole = Role::findOrCreate('instructor', 'web');
        $managerRole = Role::findOrCreate('manager', 'web');
        $instructorRankId = MilitaryRank::query()->where('code', 'senior_lieutenant_colonel')->value('id');
        $reviewerRankId = MilitaryRank::query()->where('code', 'colonel')->value('id');

        $instructorUser = User::query()->updateOrCreate(
            ['email' => self::INSTRUCTOR_EMAIL],
            [
                'name' => $instructor->name,
                'code' => $instructor->code,
                'password' => Hash::make('password'),
                'unit_id' => $instructor->unit_id,
                'instructor_id' => $instructor->id,
                'role_id' => $instructorRole->id,
                'military_rank_id' => $instructorRankId,
                'status' => 1,
                'user_type' => 'instructor',
                'email_verified_at' => now(),
            ]
        );
        $instructorUser->syncRoles([$instructorRole]);

        $reviewer = User::query()->updateOrCreate(
            ['email' => self::REVIEWER_EMAIL],
            [
                'name' => 'Quản lý Khoa Điều dưỡng (Demo)',
                'code' => 'QL-K7-DEMO',
                'password' => Hash::make('password'),
                'unit_id' => $instructor->unit_id,
                'instructor_id' => null,
                'role_id' => $managerRole->id,
                'military_rank_id' => $reviewerRankId,
                'status' => 1,
                // Loại tài khoản nội bộ; quyền quản lý được xác định bằng role manager.
                'user_type' => 'internal_user',
                'email_verified_at' => now(),
            ]
        );
        $reviewer->syncRoles([$managerRole]);

        return [$instructorUser, $reviewer];
    }

    private function configureInstructor(
        Instructor $instructor,
        User $instructorUser,
        User $reviewer
    ): void {
        $objectType = ObjectType::query()->where('code', '02')->firstOrFail();
        $position = Position::query()->where('name', 'Giảng viên')->firstOrFail();
        $department = AcademicDepartment::withTrashed()->firstOrNew([
            'unit_id' => $instructor->unit_id,
            'code' => 'K7-DEMO-BM',
        ]);
        $department->fill([
            'name' => 'Bộ môn Điều dưỡng cơ bản (Demo)',
            'description' => self::DEMO_MARKER.' Bộ môn dùng kiểm thử vượt định mức.',
            'is_active' => true,
            'sort_order' => 99,
            'created_by' => $reviewer->id,
            'updated_by' => $reviewer->id,
        ]);
        $department->deleted_at = null;
        $department->save();

        $instructor->update([
            'department_id' => $department->id,
            'object_type_id' => $objectType->id,
            'position_id' => $position->id,
            'updated_by' => $reviewer->id,
        ]);
        $instructorUser->update([
            'object_type_id' => $objectType->id,
            'position_id' => $position->id,
        ]);
    }

    private function seedConversionRecords(
        Instructor $instructor,
        User $instructorUser,
        User $reviewer,
        string $evidencePath
    ): void {
        $rows = [
            ['HD-09', 'Hướng dẫn 2 khóa luận tốt nghiệp', '2026-10-15', 2, ConversionRecord::STATUS_APPROVED],
            ['HD-16', 'Biên soạn 10 bộ đề thi trắc nghiệm', '2026-11-05', 10, ConversionRecord::STATUS_APPROVED],
            ['HD-22', 'Chấm 80 bài tự luận học phần', '2026-01-12', 80, ConversionRecord::STATUS_SUBMITTED],
            ['HD-20', 'Coi thi học phần 12 tiết', '2026-02-18', 12, ConversionRecord::STATUS_DRAFT],
            ['HD-34', 'Chấm 2 khóa luận cần bổ sung minh chứng', '2026-03-20', 2, ConversionRecord::STATUS_REJECTED],
        ];

        foreach ($rows as [$categoryCode, $name, $date, $quantity, $status]) {
            $category = ConversionCategory::query()->where('code', $categoryCode)->firstOrFail();
            $record = ConversionRecord::withTrashed()
                ->firstOrNew([
                    'instructor_id' => $instructor->id,
                    'activity_name' => self::DEMO_MARKER.' '.$name,
                    'period_mode' => app(PeriodService::class)->mode(),
                ]);
            $isApproved = $status === ConversionRecord::STATUS_APPROVED;
            $wasReviewed = in_array($status, [
                ConversionRecord::STATUS_APPROVED,
                ConversionRecord::STATUS_REJECTED,
            ], true);
            $record->fill([
                'conversion_category_id' => $category->id,
                'activity_date' => $date,
                'year' => self::YEAR,
                'period_mode' => app(PeriodService::class)->mode(),
                'quantity' => $quantity,
                'converted_hours' => $category->calculateHours((float) $quantity),
                'notes' => self::DEMO_MARKER.' Dữ liệu minh họa trạng thái '.$status.'.',
                'evidence_path' => $evidencePath,
                'status' => $status,
                'created_by' => $instructorUser->id,
                'updated_by' => $wasReviewed ? $reviewer->id : $instructorUser->id,
                'approved_by' => $isApproved ? $reviewer->id : null,
                'approved_at' => $isApproved ? now()->subDays(10) : null,
            ]);
            $record->deleted_at = null;
            $record->save();
        }
    }

    private function seedResearchRecords(
        Instructor $instructor,
        User $instructorUser,
        User $reviewer,
        string $evidencePath
    ): void {
        $rows = [
            ['NCKH-10', 'Bài báo chăm sóc người bệnh sau phẫu thuật', 'Tạp chí Y học Việt Nam', '2026-10-20', ResearchRecord::STATUS_APPROVED],
            ['NCKH-05', 'Sáng kiến bộ công cụ đánh giá kỹ năng điều dưỡng', 'Hội đồng khoa học nhà trường', '2026-12-15', ResearchRecord::STATUS_APPROVED],
            ['NCKH-14', 'Hướng dẫn học viên nghiên cứu đạt loại xuất sắc', 'Khoa Điều dưỡng', '2026-01-20', ResearchRecord::STATUS_APPROVED],
            ['NCKH-09', 'Giáo trình thực hành điều dưỡng cơ bản', 'Nhà xuất bản Quân đội nhân dân', '2026-02-10', ResearchRecord::STATUS_SUBMITTED],
            ['NCKH-11', 'Báo cáo giải pháp nâng cao chất lượng thực hành', 'Hội thảo khoa học nhà trường', '2026-03-05', ResearchRecord::STATUS_DRAFT],
            ['NCKH-15', 'Hướng dẫn đề tài học viên cần bổ sung biên bản', 'Khoa Điều dưỡng', '2026-04-12', ResearchRecord::STATUS_REJECTED],
        ];

        foreach ($rows as [$categoryCode, $name, $place, $date, $status]) {
            $category = ResearchCategory::query()->where('code', $categoryCode)->firstOrFail();
            $record = ResearchRecord::withTrashed()
                ->firstOrNew([
                    'instructor_id' => $instructor->id,
                    'product_name' => self::DEMO_MARKER.' '.$name,
                    'period_mode' => app(PeriodService::class)->mode(),
                ]);
            $isApproved = $status === ResearchRecord::STATUS_APPROVED;
            $wasReviewed = in_array($status, [
                ResearchRecord::STATUS_APPROVED,
                ResearchRecord::STATUS_REJECTED,
            ], true);
            $record->fill([
                'research_category_id' => $category->id,
                'role' => 'Chủ nhiệm / tác giả chính',
                'participation_type' => ResearchRecord::PARTICIPATION_LEAD,
                'publication_date' => $date,
                'publication_place' => $place,
                'acceptance_date' => $date,
                'year' => self::YEAR,
                'period_mode' => app(PeriodService::class)->mode(),
                'member_count' => 1,
                'duration_years' => 1,
                'annual_product_hours' => (float) $category->research_hours,
                'calculated_hours' => (float) $category->research_hours,
                'contribution_percent' => null,
                'converted_hours' => (float) $category->research_hours,
                'hours_adjustment_note' => null,
                'notes' => self::DEMO_MARKER.' Dữ liệu minh họa trạng thái '.$status.'.',
                'evidence_path' => $evidencePath,
                'status' => $status,
                'created_by' => $instructorUser->id,
                'updated_by' => $wasReviewed ? $reviewer->id : $instructorUser->id,
                'approved_by' => $isApproved ? $reviewer->id : null,
                'approved_at' => $isApproved ? now()->subDays(8) : null,
            ]);
            $record->deleted_at = null;
            $record->save();

            $record->members()->delete();
            ResearchRecordMember::query()->create([
                'research_record_id' => $record->id,
                'instructor_id' => $instructor->id,
                'role' => 'Chủ nhiệm / tác giả chính',
                'participation_type' => ResearchRecord::PARTICIPATION_LEAD,
                'contribution_percent' => 100,
                'converted_hours' => (float) $category->research_hours,
                'is_declarant' => true,
                'sort_order' => 0,
            ]);
        }
    }

    private function seedNormReductionAndExchange(Instructor $instructor, User $reviewer): void
    {
        InstructorNormReduction::query()->updateOrCreate(
            [
                'instructor_id' => $instructor->id,
                'title' => self::DEMO_MARKER.' Điều động hỗ trợ tuyển sinh',
                'period_mode' => app(PeriodService::class)->mode(),
            ],
            [
                'year' => self::YEAR,
                'type' => InstructorNormReduction::TYPE_SPECIAL_DUTY,
                'note' => 'Giảm 5% định mức để minh họa Điều 11.3.',
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-28',
                'days' => 19,
                'reduction_percent' => 5,
                'is_active' => true,
                'created_by' => $reviewer->id,
                'updated_by' => $reviewer->id,
            ]
        );

        $exchange = HourExchangeRecord::withTrashed()
            ->firstOrNew([
                'instructor_id' => $instructor->id,
                'notes' => self::DEMO_MARKER.' Chuyển 30 giờ NCKH sang HĐ chuyên môn.',
                'period_mode' => app(PeriodService::class)->mode(),
            ]);
        $exchange->fill([
            'year' => self::YEAR,
            'period_mode' => app(PeriodService::class)->mode(),
            'direction' => HourExchangeRecord::DIRECTION_NCKH_TO_CM,
            'source_hours' => 30,
            'target_hours' => 10,
            'rate' => 1 / 3,
            'created_by' => $reviewer->id,
        ]);
        $exchange->deleted_at = null;
        $exchange->save();
    }

    private function seedCalculatedResult(Instructor $instructor, User $reviewer): void
    {
        $instructor->refresh()->load(['unit', 'objectType', 'position']);
        $preview = app(CalculationService::class)
            ->previewMemberForDepartment($instructor, self::YEAR);

        $fields = [
            'instructor_id',
            'year',
            'period_mode',
            'object_type_id',
            'position_id',
            'declaration_from_date',
            'declaration_to_date',
            'schedule_teaching_hours',
            'other_teaching_hours',
            'teaching_hours',
            'conversion_hours',
            'research_hours',
            'total_standard_hours',
            'standard_norm_hours',
            'standard_difference',
            'min_classroom_hours',
            'meets_standard',
            'meets_classroom',
            'research_norm_hours',
            'research_difference',
            'meets_research',
            'meets_overall',
        ];
        $payload = collect($preview)->only($fields)->toArray();
        $payload += [
            'status' => YearlyResult::STATUS_CALCULATED,
            'calculated_by' => $reviewer->id,
            'calculated_at' => now()->subDay(),
            'locked_by' => null,
            'locked_at' => null,
        ];

        YearlyResult::query()->updateOrCreate(
            [
                'instructor_id' => $instructor->id,
                'year' => self::YEAR,
                'period_mode' => app(PeriodService::class)->mode(),
            ],
            $payload
        );

        CalculationLog::query()->updateOrCreate(
            [
                'year' => self::YEAR,
                'period_mode' => app(PeriodService::class)->mode(),
                'action' => CalculationLog::ACTION_CALCULATE,
                'notes' => self::DEMO_MARKER.' Kết quả tính mẫu.',
            ],
            [
                'instructors_processed' => 1,
                'instructors_skipped' => 0,
                'performed_by' => $reviewer->id,
                'created_at' => now()->subDay(),
            ]
        );
    }

    private function seedDepartmentOvertime(Instructor $instructor, User $reviewer): void
    {
        $instructor->refresh();
        $result = YearlyResult::query()
            ->currentPeriod()
            ->where('instructor_id', $instructor->id)
            ->where('year', self::YEAR)
            ->firstOrFail();
        $excess = max(0, round(
            (float) $result->overtime_eligible_hours - (float) $result->standard_norm_hours,
            2
        ));

        DepartmentOvertimePool::query()->updateOrCreate(
            [
                'department_id' => $instructor->department_id,
                'year' => self::YEAR,
                'period_mode' => app(PeriodService::class)->mode(),
            ],
            [
                'pool_must_hours' => $result->standard_norm_hours,
                'pool_done_hours' => $result->overtime_eligible_hours,
                'pool_excess_hours' => $excess,
                'member_count' => 1,
                'member_snapshot' => [[
                    'instructor_id' => $instructor->id,
                    'name' => $instructor->name,
                    'code' => $instructor->code,
                    'must_hours' => (float) $result->standard_norm_hours,
                    'done_hours' => (float) $result->overtime_eligible_hours,
                    'excess_hours' => $excess,
                    'reduction_percent' => 5,
                    'source' => 'yearly_result',
                ]],
                'status' => DepartmentOvertimePool::STATUS_DRAFT,
                'calculated_by' => $reviewer->id,
                'calculated_at' => now()->subDay(),
                'locked_by' => null,
                'locked_at' => null,
                'note' => self::DEMO_MARKER.' Pool chưa khóa để người quản lý thử phân bổ.',
            ]
        );
    }

    private function writeEvidence(string $path, string $title, array $lines): string
    {
        $word = new PhpWord;
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(13);
        $section = $word->addSection();
        $section->addText($title, ['bold' => true, 'size' => 16], ['alignment' => 'center']);
        $section->addTextBreak();
        foreach ($lines as $line) {
            $section->addText($line, [], ['spaceAfter' => 120]);
        }
        $section->addTextBreak();
        $section->addText('Ngày tạo minh chứng demo: '.now()->format('d/m/Y H:i'), ['italic' => true]);

        $tmpBase = tempnam(sys_get_temp_dir(), 'standard_hours_demo_');
        if ($tmpBase === false) {
            throw new \RuntimeException('Không tạo được file tạm cho minh chứng demo.');
        }
        $tmp = $tmpBase.'.docx';
        @unlink($tmpBase);
        WordIOFactory::createWriter($word, 'Word2007')->save($tmp);
        $contents = file_get_contents($tmp);
        if ($contents === false) {
            throw new \RuntimeException('Không đọc được file minh chứng demo vừa tạo.');
        }
        Storage::disk('public')->put($path, $contents);
        @unlink($tmp);

        return $path;
    }
}
