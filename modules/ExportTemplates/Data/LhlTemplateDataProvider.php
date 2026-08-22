<?php

namespace Modules\ExportTemplates\Data;

use App\Services\DigitalSignatureService;
use App\Support\SystemSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\ExportTemplates\Contracts\TemplateDataProviderInterface;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\TrainingSchedule\Models\TrainingSchedule;

class LhlTemplateDataProvider implements TemplateDataProviderInterface
{
    public function __construct(
        private readonly LhlScheduleGroupService $groupService,
        private readonly DigitalSignatureService $signatureService
    ) {}

    public function featureKey(): string
    {
        return 'lhl.training_plan';
    }

    public function schema(): array
    {
        return [
            'version' => 3,
            'feature_key' => $this->featureKey(),
            'groups' => [
                $this->group('organization', 'Đơn vị ban hành', [
                    $this->field('organization.parent_name', 'Cơ quan cấp trên'),
                    $this->field('organization.name', 'Tên đơn vị'),
                    $this->field('organization.title', 'Tên biểu mẫu'),
                    $this->field('organization.respect_line', 'Dòng kính gửi'),
                ]),
                $this->group('class', 'Thông tin lớp', [
                    $this->field('class.name', 'Tên lớp'),
                    $this->field('class.code', 'Mã lớp'),
                    $this->field('class.cohort', 'Khóa'),
                    $this->field('class.enrollment', 'Sĩ số', 'number'),
                    $this->field('class.capacity', 'Sĩ số tối đa', 'number'),
                    $this->field('class.management_unit', 'Đơn vị quản lý'),
                    $this->field('class.room', 'Phòng học'),
                ]),
                $this->group('time', 'Thời gian', [
                    $this->field('time.month', 'Tháng'),
                    $this->field('time.week', 'Tuần'),
                    $this->field('time.start_date', 'Từ ngày', 'date'),
                    $this->field('time.end_date', 'Đến ngày', 'date'),
                    $this->field('time.semester', 'Học kỳ'),
                    $this->field('time.academic_year', 'Năm học'),
                ]),
                $this->group('schedule', 'Thông tin lịch', [
                    $this->field('schedule.name', 'Tên lịch'),
                    $this->field('schedule.code', 'Mã lịch'),
                    $this->field('schedule.abbreviation', 'Tên viết tắt lịch'),
                ]),
                $this->group('weeks', 'Danh sách tuần', [
                    $this->field('weeks[].number', 'Số tuần', 'number'),
                    $this->field('weeks[].month', 'Tháng'),
                    $this->field('weeks[].start_date', 'Ngày bắt đầu', 'date'),
                    $this->field('weeks[].end_date', 'Ngày kết thúc', 'date'),
                    $this->field('weeks[].date_range', 'Khoảng ngày'),
                ], 'collection'),
                $this->group('schedule_groups', 'Lịch học đã gom tiết liên tiếp', [
                    $this->field('schedule_groups[].week_number', 'Số tuần', 'number'),
                    $this->field('schedule_groups[].week_label', 'Nhãn tuần'),
                    $this->field('schedule_groups[].date', 'Ngày', 'date'),
                    $this->field('schedule_groups[].date_display', 'Ngày hiển thị'),
                    $this->field('schedule_groups[].day', 'Ngày trong tháng', 'number'),
                    $this->field('schedule_groups[].weekday', 'Thứ'),
                    $this->field('schedule_groups[].period_start', 'Tiết bắt đầu', 'number'),
                    $this->field('schedule_groups[].period_end', 'Tiết kết thúc', 'number'),
                    $this->field('schedule_groups[].period_label', 'Khoảng tiết'),
                    $this->field('schedule_groups[].subject_name', 'Môn học'),
                    $this->field('schedule_groups[].subject_code', 'Mã môn'),
                    $this->field('schedule_groups[].subject_short_name', 'Tên viết tắt môn'),
                    $this->field('schedule_groups[].teacher_name', 'Giảng viên'),
                    $this->field('schedule_groups[].teacher_code', 'Mã giảng viên'),
                    $this->field('schedule_groups[].teacher_unit', 'Đơn vị giảng viên'),
                    $this->field('schedule_groups[].teacher_position', 'Chức vụ giảng viên'),
                    $this->field('schedule_groups[].location', 'Địa điểm'),
                    $this->field('schedule_groups[].content', 'Nội dung'),
                    $this->field('schedule_groups[].lesson_type', 'Loại tiết'),
                    $this->field('schedule_groups[].note', 'Ghi chú'),
                ], 'collection'),
                $this->group(
                    'schedule_days',
                    'Lịch theo từng ngày — biến riêng cho từng nhóm và từng tiết',
                    $this->scheduleDayFields(),
                    'collection'
                ),
                $this->group('subjects', 'Danh mục môn học trong lịch', [
                    $this->field('subjects[].name', 'Tên môn học'),
                    $this->field('subjects[].code', 'Mã môn học'),
                    $this->field('subjects[].short_name', 'Tên viết tắt'),
                    $this->field('subjects[].credits', 'Số tín chỉ', 'number'),
                    $this->field('subjects[].theory_hours', 'Số tiết lý thuyết', 'number'),
                    $this->field('subjects[].practice_hours', 'Số tiết thực hành', 'number'),
                    $this->field('subjects[].self_study_hours', 'Số tiết tự học', 'number'),
                    $this->field('subjects[].exam_hours', 'Số tiết thi', 'number'),
                    $this->field('subjects[].review_hours', 'Số tiết ôn', 'number'),
                    $this->field('subjects[].faculty_code', 'Mã khoa'),
                ], 'collection'),
                $this->group('signers', 'Người ký', [
                    $this->field('signers[].key', 'Vị trí ký'),
                    $this->field('signers[].role_line1', 'Chức danh dòng 1'),
                    $this->field('signers[].role_line2', 'Chức danh dòng 2'),
                    $this->field('signers[].name', 'Họ tên người ký'),
                    $this->field('signers[].image', 'Ảnh chữ ký', 'image'),
                    $this->field('signers[].enabled', 'Hiển thị chữ ký', 'boolean'),
                ], 'collection'),
                $this->group('signature', 'Người ký theo vị trí', [
                    $this->field('signature.schedule_maker.role_line1', 'Người làm lịch - chức danh 1'),
                    $this->field('signature.schedule_maker.role_line2', 'Người làm lịch - chức danh 2'),
                    $this->field('signature.schedule_maker.name', 'Người làm lịch - họ tên'),
                    $this->field('signature.schedule_maker.image', 'Người làm lịch - chữ ký', 'image'),
                    $this->field('signature.training_manager.role_line1', 'Trưởng phòng đào tạo - chức danh 1'),
                    $this->field('signature.training_manager.role_line2', 'Trưởng phòng đào tạo - chức danh 2'),
                    $this->field('signature.training_manager.name', 'Trưởng phòng đào tạo - họ tên'),
                    $this->field('signature.training_manager.image', 'Trưởng phòng đào tạo - chữ ký', 'image'),
                    $this->field('signature.principal.role_line1', 'Hiệu trưởng - chức danh 1'),
                    $this->field('signature.principal.role_line2', 'Hiệu trưởng - chức danh 2'),
                    $this->field('signature.principal.name', 'Hiệu trưởng - họ tên'),
                    $this->field('signature.principal.image', 'Hiệu trưởng - chữ ký', 'image'),
                ]),
                $this->group('summary', 'Tổng hợp', [
                    $this->field('summary.total_groups', 'Tổng số nhóm tiết', 'number'),
                    $this->field('summary.total_periods', 'Tổng số tiết', 'number'),
                    $this->field('summary.total_subjects', 'Tổng số môn', 'number'),
                ]),
                $this->group('generated', 'Thông tin tạo tài liệu', [
                    $this->field('generated.at', 'Thời điểm tạo', 'date'),
                    $this->field('generated.by', 'Người tạo'),
                ]),
            ],
        ];
    }

    public function mockData(): array
    {
        $start = CarbonImmutable::parse('2026-03-02');
        $end = CarbonImmutable::parse('2026-03-08');
        $groups = [
            $this->mockGroup($start, 1, 3, 'TTT', 'Thuốc thông thường'),
            $this->mockGroup($start, 4, 5, 'GPSL', 'Giải phẫu sinh lý'),
            $this->mockGroup($start, 6, 9, 'TTT', 'Thuốc thông thường'),
        ];

        $signers = [
            $this->signer('nguoi_lam_lich', 'NGƯỜI LÀM LỊCH', '', 'Thượng úy Lưu Phước Bảo Trung'),
            $this->signer('kt_truong_phong', 'KT. TRƯỞNG PHÒNG', 'PHÓ TRƯỞNG PHÒNG', 'Thượng tá Đinh Văn Hà'),
            $this->signer('kt_hieu_truong', 'KT. HIỆU TRƯỞNG', 'PHÓ HIỆU TRƯỞNG ĐÀO TẠO', 'Đại tá Nguyễn Ngọc Huy'),
        ];

        return [
            'organization' => [
                'parent_name' => (string) SystemSettings::get(
                    'shared',
                    'parent_organization_name',
                    'TỔNG CỤC HẬU CẦN - KỸ THUẬT'
                ),
                'name' => (string) SystemSettings::get(
                    'shared',
                    'organization_name',
                    'TRƯỜNG CAO ĐẲNG HẬU CẦN 2'
                ),
                'title' => 'LỊCH HUẤN LUYỆN',
                'respect_line' => 'Kính gửi:…………………………………………………..',
            ],
            'class' => [
                'name' => 'Y54',
                'code' => 'Y54',
                'cohort' => 'Khóa 54',
                'enrollment' => 60,
                'capacity' => 60,
                'management_unit' => 'Đại đội 4/Tiểu đoàn 2',
                'room' => '101/H3',
            ],
            'time' => [
                'month' => 'Tháng 3/2026',
                'week' => 'Tuần 1',
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'semester' => 'Học kỳ 2',
                'academic_year' => '2025-2026',
            ],
            'schedule' => [
                'name' => 'Lịch huấn luyện lớp Y54',
                'code' => 'LHL-Y54-HK2',
                'abbreviation' => 'LHL Y54',
            ],
            'weeks' => $this->weeks($start, $end),
            'schedule_groups' => $groups,
            'schedule_days' => $this->scheduleDays($groups, $start, $end),
            'subjects' => [
                $this->mockSubject('Thuốc thông thường', 'TTT'),
                $this->mockSubject('Giải phẫu sinh lý', 'GPSL'),
            ],
            'signers' => $signers,
            'signature' => $this->signatureAliases($signers),
            'summary' => [
                'total_groups' => count($groups),
                'total_periods' => 9,
                'total_subjects' => 2,
            ],
            'generated' => [
                'at' => '2026-03-01 08:00:00',
                'by' => 'Nguyễn Văn Quản trị',
            ],
        ];
    }

    public function load(array $context): array
    {
        $ids = $this->scheduleIds($context);
        if ($ids === []) {
            throw new \InvalidArgumentException(
                'Cần truyền training_schedule_id hoặc training_schedule_ids để nạp dữ liệu LHL.'
            );
        }

        $schedules = TrainingSchedule::query()
            ->with(['class.classroom.building', 'classModel.classroom.building', 'classroom.building'])
            ->whereIn('id', $ids)
            ->orderBy('start_date')
            ->get();

        if ($schedules->isEmpty()) {
            throw new \InvalidArgumentException('Không tìm thấy lịch huấn luyện trong phạm vi yêu cầu.');
        }

        $details = ScheduleDetail::query()
            ->with([
                'subject',
                'subjectLesson',
                'instructor.unit',
                'instructor.position',
                'classroom.building',
            ])
            ->whereIn('training_schedule_id', $schedules->pluck('id'))
            ->when(
                ! empty($context['start_date']),
                fn ($query) => $query->whereDate('date', '>=', $context['start_date'])
            )
            ->when(
                ! empty($context['end_date']),
                fn ($query) => $query->whereDate('date', '<=', $context['end_date'])
            )
            ->get();

        $first = $schedules->first();
        $class = $first->class ?: $first->classModel;
        $start = $this->date($context['start_date'] ?? $schedules->min('start_date') ?? now());
        $end = $this->date($context['end_date'] ?? $schedules->max('end_date') ?? $start);
        $groups = $this->enrichGroups($this->groupService->group($details), $start);
        $subjects = $this->subjects($details);
        $signers = $this->realSigners(
            (array) ($context['export_meta'] ?? $context['signers'] ?? [])
        );
        $organization = (array) ($context['organization'] ?? []);

        return [
            'organization' => [
                'parent_name' => (string) ($organization['parent_name']
                    ?? $context['org_parent']
                    ?? SystemSettings::get(
                        'shared',
                        'parent_organization_name',
                        'TỔNG CỤC HẬU CẦN - KỸ THUẬT'
                    )),
                'name' => (string) ($organization['name']
                    ?? $context['org_name']
                    ?? SystemSettings::get(
                        'shared',
                        'organization_name',
                        'TRƯỜNG CAO ĐẲNG HẬU CẦN 2'
                    )),
                'title' => (string) ($organization['title']
                    ?? $context['title']
                    ?? config('lhl_export.title', 'LỊCH HUẤN LUYỆN')),
                'respect_line' => (string) ($organization['respect_line']
                    ?? $context['respect_line']
                    ?? config('lhl_export.respect_line', '')),
            ],
            'class' => [
                'name' => (string) ($class?->name ?? $first->class_code ?? ''),
                'code' => (string) ($class?->code ?? $first->class_code ?? ''),
                'cohort' => (string) ($context['cohort'] ?? ''),
                'enrollment' => (int) ($context['class_size'] ?? $class?->current_students ?? 0),
                'capacity' => (int) ($class?->max_students ?? 0),
                'management_unit' => (string) ($context['unit_name']
                    ?? $class?->management_unit
                    ?? ''),
                'room' => (string) ($context['classroom']
                    ?? $this->room($class?->classroom ?: $first->classroom)),
            ],
            'time' => [
                'month' => 'Tháng '.$start->format('m/Y'),
                'week' => 'Tuần '.($context['week'] ?? 1),
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'semester' => (string) ($context['semester_line']
                    ?? $first->semester_text
                    ?? $first->semester
                    ?? ''),
                'academic_year' => (string) ($context['academic_year']
                    ?? $first->academic_year
                    ?? ''),
            ],
            'schedule' => [
                'name' => (string) ($first->name ?? ''),
                'code' => (string) ($first->code ?? ''),
                'abbreviation' => (string) ($first->abbreviation ?? ''),
            ],
            'weeks' => $this->weeks($start, $end),
            'schedule_groups' => $groups,
            'schedule_days' => $this->scheduleDays($groups, $start, $end),
            'subjects' => $subjects,
            'signers' => $signers,
            'signature' => $this->signatureAliases($signers),
            'summary' => [
                'total_groups' => count($groups),
                'total_periods' => array_sum(array_map(
                    static fn (array $group): int => $group['period_end'] - $group['period_start'] + 1,
                    $groups
                )),
                'total_subjects' => count($subjects),
            ],
            'generated' => [
                'at' => now()->format('Y-m-d H:i:s'),
                'by' => (string) ($context['generated_by'] ?? auth()->user()?->name ?? 'Hệ thống'),
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function scheduleIds(array $context): array
    {
        $ids = $context['training_schedule_ids'] ?? [$context['training_schedule_id'] ?? null];

        return array_values(array_unique(array_filter(
            array_map('intval', is_array($ids) ? $ids : [$ids]),
            static fn (int $id): bool => $id > 0
        )));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function subjects(Collection $details): array
    {
        return $details
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->map(fn ($subject) => [
                'name' => (string) $subject->name,
                'code' => (string) $subject->code,
                'short_name' => (string) $subject->short_label,
                'credits' => (int) ($subject->credits ?? 0),
                'theory_hours' => (int) ($subject->theory_hours ?? 0),
                'practice_hours' => (int) ($subject->practice_hours ?? 0),
                'self_study_hours' => (int) ($subject->self_study_hours ?? 0),
                'exam_hours' => (int) ($subject->exam_hours ?? 0),
                'review_hours' => (int) ($subject->review_hours ?? 0),
                'faculty_code' => (string) ($subject->faculty_code ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function realSigners(array $meta): array
    {
        return array_map(
            fn (array $signer): array => [
                'key' => (string) ($signer['key'] ?? ''),
                'role_line1' => (string) ($signer['role_line1'] ?? ''),
                'role_line2' => (string) ($signer['role_line2'] ?? ''),
                'name' => (string) ($signer['name'] ?? ''),
                'image' => (string) ($signer['image'] ?? ''),
                'enabled' => (bool) ($signer['enabled'] ?? true),
            ],
            $this->signatureService->resolveExportSigners($meta)
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function weeks(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $weeks = [];
        $cursor = $start->startOfWeek();
        $number = 1;

        while ($cursor->lte($end)) {
            // Giữ nguyên biên ngày được yêu cầu (đặc biệt tuần 9: 17–23),
            // không suy diễn lại theo Sunday boundary khiến mất ngày cuối.
            $weekStart = $cursor->lt($start) ? $start : $cursor;
            $weekEnd = $weekStart->addDays(6)->gt($end) ? $end : $weekStart->addDays(6);
            $weeks[] = [
                'number' => $number++,
                'month' => $weekStart->format('m/Y'),
                'start_date' => $weekStart->format('Y-m-d'),
                'end_date' => $weekEnd->format('Y-m-d'),
                'date_range' => $weekStart->format('d/m').'-'.$weekEnd->format('d/m'),
            ];
            $cursor = $weekEnd->addDay();
        }

        return $weeks;
    }

    private function date(mixed $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value)->startOfDay();
    }

    private function room(mixed $classroom): string
    {
        $room = trim((string) ($classroom?->name ?? ''));
        $building = trim((string) ($classroom?->building?->code ?: $classroom?->building?->name));

        return $room !== '' && $building !== '' ? "{$room}/{$building}" : ($room ?: $building);
    }

    private function group(string $key, string $label, array $children, string $type = 'object'): array
    {
        return compact('key', 'label', 'type', 'children');
    }

    private function field(
        string $key,
        string $label,
        string $type = 'string',
        ?string $description = null
    ): array {
        $field = compact('key', 'label', 'type') + ['bindable' => true];
        if ($description !== null) {
            $field['description'] = $description;
        }

        return $field;
    }

    /**
     * Biến collection cũ `schedule_groups[]` phù hợp bảng lặp, nhưng không
     * đủ cho mẫu có các ô cố định. Các alias dưới đây cho phép gán chính xác
     * từng nhóm trong ngày hoặc từng tiết 1-9 mà không phải nhập placeholder.
     *
     * @return list<array<string, mixed>>
     */
    private function scheduleDayFields(): array
    {
        $fields = [
            $this->field('schedule_days[].week_number', 'Ngày lịch — Số tuần', 'number'),
            $this->field('schedule_days[].week_label', 'Ngày lịch — Nhãn tuần'),
            $this->field('schedule_days[].date', 'Ngày lịch — Ngày thực hiện', 'date'),
            $this->field('schedule_days[].date_display', 'Ngày lịch — Ngày hiển thị'),
            $this->field('schedule_days[].day', 'Ngày lịch — Ngày trong tháng', 'number'),
            $this->field('schedule_days[].weekday', 'Ngày lịch — Thứ'),
        ];

        for ($group = 1; $group <= 9; $group++) {
            $prefix = "schedule_days[].group_{$group}";
            $description = "nhóm môn liên tiếp thứ {$group} trong ngày, sau khi tự động gom đúng lịch đã xếp";
            $fields = array_merge($fields, [
                $this->field("{$prefix}.exists", "Nhóm {$group} — Có dữ liệu", 'boolean', "Cho biết {$description} có tồn tại hay không."),
                $this->field("{$prefix}.period_start", "Nhóm {$group} — Tiết bắt đầu", 'number', "Xuất tiết bắt đầu của {$description}."),
                $this->field("{$prefix}.period_end", "Nhóm {$group} — Tiết kết thúc", 'number', "Xuất tiết kết thúc của {$description}."),
                $this->field("{$prefix}.period_label", "Nhóm {$group} — Khoảng tiết", 'string', "Xuất khoảng tiết của {$description}, ví dụ 1-3 hoặc 4-5."),
                $this->field("{$prefix}.subject_name", "Nhóm {$group} — Tên môn", 'string', "Xuất tên đầy đủ của môn thuộc {$description}."),
                $this->field("{$prefix}.subject_code", "Nhóm {$group} — Mã môn", 'string', "Xuất mã môn thuộc {$description}."),
                $this->field("{$prefix}.subject_short_name", "Nhóm {$group} — Tên viết tắt môn", 'string', "Xuất tên viết tắt môn thuộc {$description}."),
                $this->field("{$prefix}.teacher_name", "Nhóm {$group} — Giảng viên", 'string', "Xuất giảng viên phụ trách {$description}."),
                $this->field("{$prefix}.teacher_unit", "Nhóm {$group} — Đơn vị giảng viên", 'string', "Xuất đơn vị của giảng viên thuộc {$description}."),
                $this->field("{$prefix}.location", "Nhóm {$group} — Địa điểm", 'string', "Xuất địa điểm học của {$description}."),
                $this->field("{$prefix}.content", "Nhóm {$group} — Nội dung", 'string', "Xuất bài học hoặc nội dung của {$description}."),
                $this->field("{$prefix}.lesson_type", "Nhóm {$group} — Loại tiết", 'string', "Xuất loại tiết của {$description}."),
                $this->field("{$prefix}.note", "Nhóm {$group} — Ghi chú", 'string', "Xuất ghi chú của {$description}."),
            ]);
        }

        for ($period = 1; $period <= 9; $period++) {
            $prefix = "schedule_days[].period_{$period}";
            $description = "tiết {$period} thực tế trong lịch đã xếp";
            $fields = array_merge($fields, [
                $this->field("{$prefix}.exists", "Tiết {$period} — Có dữ liệu", 'boolean', "Cho biết {$description} đã có môn hay chưa."),
                $this->field("{$prefix}.period_number", "Tiết {$period} — Số tiết", 'number', "Luôn xuất số {$period} cho ô tiết tương ứng."),
                $this->field("{$prefix}.group_start", "Tiết {$period} — Đầu nhóm", 'boolean', "Đúng khi tiết {$period} là tiết đầu của một nhóm môn liên tiếp."),
                $this->field("{$prefix}.group_end", "Tiết {$period} — Cuối nhóm", 'boolean', "Đúng khi tiết {$period} là tiết cuối của một nhóm môn liên tiếp."),
                $this->field("{$prefix}.period_label", "Tiết {$period} — Khoảng nhóm", 'string', "Xuất khoảng nhóm chứa {$description}; ví dụ tiết 4 thuộc nhóm 4-5 sẽ xuất 4-5."),
                $this->field("{$prefix}.subject_name", "Tiết {$period} — Tên môn", 'string', "Xuất tên đầy đủ môn được xếp tại {$description}."),
                $this->field("{$prefix}.subject_code", "Tiết {$period} — Mã môn", 'string', "Xuất mã môn được xếp tại {$description}."),
                $this->field("{$prefix}.subject_short_name", "Tiết {$period} — Tên viết tắt môn", 'string', "Xuất tên viết tắt môn được xếp tại {$description}."),
                $this->field("{$prefix}.teacher_name", "Tiết {$period} — Giảng viên", 'string', "Xuất giảng viên được xếp tại {$description}."),
                $this->field("{$prefix}.teacher_unit", "Tiết {$period} — Đơn vị giảng viên", 'string', "Xuất đơn vị giảng viên tại {$description}."),
                $this->field("{$prefix}.location", "Tiết {$period} — Địa điểm", 'string', "Xuất địa điểm học tại {$description}."),
                $this->field("{$prefix}.content", "Tiết {$period} — Nội dung", 'string', "Xuất bài học hoặc nội dung tại {$description}."),
                $this->field("{$prefix}.lesson_type", "Tiết {$period} — Loại tiết", 'string', "Xuất loại tiết tại {$description}."),
                $this->field("{$prefix}.note", "Tiết {$period} — Ghi chú", 'string', "Xuất ghi chú tại {$description}."),
            ]);
        }

        foreach ([
            'slot_1_3' => [1, 3],
            'slot_4_5' => [4, 5],
            'slot_6_9' => [6, 9],
        ] as $slotKey => [$slotStart, $slotEnd]) {
            $prefix = "schedule_days[].{$slotKey}";
            $description = "slot cố định {$slotStart}-{$slotEnd} trong ngày";
            $fields = array_merge($fields, [
                $this->field("{$prefix}.exists", "Slot {$slotStart}-{$slotEnd} — Có dữ liệu", 'boolean', "Cho biết {$description} có môn được xếp hay chưa."),
                $this->field("{$prefix}.period_label", "Slot {$slotStart}-{$slotEnd} — Khoảng tiết", 'string', "Luôn xuất {$slotStart}-{$slotEnd}; nếu môn phủ 1-5 thì môn được lặp vào cả hai slot sáng."),
                $this->field("{$prefix}.subject_name", "Slot {$slotStart}-{$slotEnd} — Tên môn", 'string', "Xuất tên môn đang nằm trong {$description}."),
                $this->field("{$prefix}.subject_code", "Slot {$slotStart}-{$slotEnd} — Mã môn", 'string', "Xuất mã môn đang nằm trong {$description}."),
                $this->field("{$prefix}.subject_short_name", "Slot {$slotStart}-{$slotEnd} — Tên viết tắt môn", 'string', "Xuất tên viết tắt môn đang nằm trong {$description}."),
                $this->field("{$prefix}.teacher_name", "Slot {$slotStart}-{$slotEnd} — Giảng viên", 'string', "Xuất giảng viên của {$description}."),
                $this->field("{$prefix}.teacher_unit", "Slot {$slotStart}-{$slotEnd} — Đơn vị giảng viên", 'string', "Xuất đơn vị giảng viên của {$description}."),
                $this->field("{$prefix}.location", "Slot {$slotStart}-{$slotEnd} — Địa điểm", 'string', "Xuất địa điểm của {$description}."),
                $this->field("{$prefix}.content", "Slot {$slotStart}-{$slotEnd} — Nội dung", 'string', "Xuất nội dung của {$description}."),
                $this->field("{$prefix}.lesson_type", "Slot {$slotStart}-{$slotEnd} — Loại tiết", 'string', "Xuất loại tiết của {$description}."),
                $this->field("{$prefix}.note", "Slot {$slotStart}-{$slotEnd} — Ghi chú", 'string', "Xuất ghi chú của {$description}."),
            ]);
        }

        return $fields;
    }

    private function mockGroup(
        CarbonImmutable $date,
        int $start,
        int $end,
        string $shortName,
        string $subjectName
    ): array {
        return [
            'week_number' => 1,
            'week_label' => 'Tuần 1',
            'date' => $date->format('Y-m-d'),
            'date_display' => $date->format('d/m'),
            'day' => (int) $date->format('d'),
            'weekday' => 'Thứ Hai',
            'period_start' => $start,
            'period_end' => $end,
            'period_label' => "{$start}-{$end}",
            'subject_name' => $subjectName,
            'subject_code' => $shortName,
            'subject_short_name' => $shortName,
            'teacher_name' => 'Đại úy Nguyễn Văn Minh',
            'teacher_code' => 'GV-0054',
            'teacher_unit' => 'Khoa Y Dược',
            'teacher_position' => 'Giảng viên',
            'location' => '101/H3',
            'content' => 'Bài học mẫu',
            'lesson_type' => 'theory',
            'note' => '',
        ];
    }

    private function mockSubject(string $name, string $code): array
    {
        return [
            'name' => $name,
            'code' => $code,
            'short_name' => $code,
            'credits' => 2,
            'theory_hours' => 15,
            'practice_hours' => 15,
            'self_study_hours' => 0,
            'exam_hours' => 2,
            'review_hours' => 0,
            'faculty_code' => 'K4',
        ];
    }

    private function signer(string $key, string $role1, string $role2, string $name): array
    {
        return [
            'key' => $key,
            'role_line1' => $role1,
            'role_line2' => $role2,
            'name' => $name,
            'image' => "signatures/lhl/{$key}.png",
            'enabled' => true,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    private function enrichGroups(array $groups, CarbonImmutable $start): array
    {
        $firstMonday = $start->startOfWeek();

        return array_map(function (array $group) use ($firstMonday): array {
            $date = $this->date($group['date']);
            $weekNumber = intdiv((int) $firstMonday->diffInDays($date->startOfWeek()), 7) + 1;

            return [
                'week_number' => $weekNumber,
                'week_label' => 'Tuần '.$weekNumber,
                'date_display' => $date->format('d/m'),
            ] + $group;
        }, $groups);
    }

    /**
     * Dựng hai cách truy cập song song cho mỗi ngày:
     * - group_1..group_9: đúng thứ tự các nhóm môn liên tiếp trong ngày;
     * - period_1..period_9: đúng môn/giảng viên/nội dung tại từng tiết vật lý.
     *
     * @param  list<array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    private function scheduleDays(
        array $groups,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): array {
        $groupsByDate = [];
        foreach ($groups as $group) {
            $date = (string) ($group['date'] ?? '');
            if ($date !== '') {
                $groupsByDate[$date][] = $group;
            }
        }

        $calendarDays = [];
        $cursor = $start->startOfDay();
        while ($cursor->lte($end)) {
            if ($cursor->dayOfWeekIso <= 5) {
                $calendarDays[$cursor->format('Y-m-d')] = $groupsByDate[$cursor->format('Y-m-d')] ?? [];
            }
            $cursor = $cursor->addDay();
        }
        foreach ($groupsByDate as $date => $dayGroups) {
            $calendarDays[$date] ??= $dayGroups;
        }
        ksort($calendarDays);

        $days = [];
        $firstMonday = $start->startOfWeek();
        foreach ($calendarDays as $date => $dayGroups) {
            usort($dayGroups, static fn (array $left, array $right): int => [
                (int) ($left['period_start'] ?? 0),
                (int) ($left['period_end'] ?? 0),
            ] <=> [
                (int) ($right['period_start'] ?? 0),
                (int) ($right['period_end'] ?? 0),
            ]);

            $calendarDate = $this->date($date);
            $first = $dayGroups[0] ?? [];
            $weekNumber = intdiv(
                (int) $firstMonday->diffInDays($calendarDate->startOfWeek()),
                7
            ) + 1;
            $day = [
                'week_number' => (int) ($first['week_number'] ?? $weekNumber),
                'week_label' => (string) ($first['week_label'] ?? 'Tuần '.$weekNumber),
                'date' => $date,
                'date_display' => (string) ($first['date_display'] ?? $calendarDate->format('d/m')),
                'day' => (int) ($first['day'] ?? $calendarDate->format('d')),
                'weekday' => (string) ($first['weekday'] ?? $this->weekday($calendarDate->dayOfWeekIso)),
            ];

            for ($index = 1; $index <= 9; $index++) {
                $day["group_{$index}"] = $this->emptyGroupSlot();
                $day["period_{$index}"] = $this->emptyPeriodSlot($index);
            }
            $day['slot_1_3'] = $this->fixedSlot($dayGroups, 1, 3);
            $day['slot_4_5'] = $this->fixedSlot($dayGroups, 4, 5);
            $day['slot_6_9'] = $this->fixedSlot($dayGroups, 6, 9);

            foreach (array_slice($dayGroups, 0, 9) as $offset => $group) {
                $groupNumber = $offset + 1;
                $groupSlot = $this->groupSlot($group);
                $day["group_{$groupNumber}"] = $groupSlot;

                $start = max(1, min(9, (int) $groupSlot['period_start']));
                $end = max($start, min(9, (int) $groupSlot['period_end']));
                for ($period = $start; $period <= $end; $period++) {
                    $day["period_{$period}"] = [
                        'exists' => true,
                        'period_number' => $period,
                        'group_start' => $period === $start,
                        'group_end' => $period === $end,
                    ] + $groupSlot;
                }
            }

            $days[] = $day;
        }

        return $days;
    }

    private function weekday(int $isoDay): string
    {
        return match ($isoDay) {
            1 => 'Thứ Hai',
            2 => 'Thứ Ba',
            3 => 'Thứ Tư',
            4 => 'Thứ Năm',
            5 => 'Thứ Sáu',
            6 => 'Thứ Bảy',
            default => 'Chủ Nhật',
        };
    }

    /** @return array<string, mixed> */
    private function groupSlot(array $group): array
    {
        return [
            'exists' => true,
            'period_start' => (int) ($group['period_start'] ?? 0),
            'period_end' => (int) ($group['period_end'] ?? 0),
            'period_label' => (string) ($group['period_label'] ?? ''),
            'subject_name' => (string) ($group['subject_name'] ?? ''),
            'subject_code' => (string) ($group['subject_code'] ?? ''),
            'subject_short_name' => (string) ($group['subject_short_name'] ?? ''),
            'teacher_name' => (string) ($group['teacher_name'] ?? ''),
            'teacher_unit' => (string) ($group['teacher_unit'] ?? ''),
            'location' => (string) ($group['location'] ?? ''),
            'content' => (string) ($group['content'] ?? ''),
            'lesson_type' => (string) ($group['lesson_type'] ?? ''),
            'note' => (string) ($group['note'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyGroupSlot(): array
    {
        return [
            'exists' => false,
            'period_start' => 0,
            'period_end' => 0,
            'period_label' => '',
            'subject_name' => '',
            'subject_code' => '',
            'subject_short_name' => '',
            'teacher_name' => '',
            'teacher_unit' => '',
            'location' => '',
            'content' => '',
            'lesson_type' => '',
            'note' => '',
        ];
    }

    /** @return array<string, mixed> */
    private function emptyPeriodSlot(int $period): array
    {
        return [
            'period_number' => $period,
            'group_start' => false,
            'group_end' => false,
        ] + $this->emptyGroupSlot();
    }

    /** @return array<string, mixed> */
    private function fixedSlot(array $groups, int $start, int $end): array
    {
        $matching = array_values(array_filter($groups, static fn (array $group): bool =>
            (int) ($group['period_start'] ?? 0) <= $start
            && (int) ($group['period_end'] ?? 0) >= $end
        ));
        if ($matching === []) {
            $matching = array_values(array_filter($groups, static fn (array $group): bool =>
                (int) ($group['period_start'] ?? 0) <= $end
                && (int) ($group['period_end'] ?? 0) >= $start
            ));
        }
        if ($matching === []) {
            return [
                'exists' => false,
                'period_label' => "{$start}-{$end}",
                'subject_name' => '',
                'subject_code' => '',
                'subject_short_name' => '',
                'teacher_name' => '',
                'teacher_unit' => '',
                'location' => '',
                'content' => '',
                'lesson_type' => '',
                'note' => '',
            ];
        }

        $slot = $this->groupSlot($matching[0]);
        $slot['period_label'] = "{$start}-{$end}";
        foreach (['subject_name', 'subject_code', 'subject_short_name', 'teacher_name', 'teacher_unit', 'location', 'content', 'lesson_type', 'note'] as $field) {
            $values = array_values(array_unique(array_filter(array_map(
                static fn (array $group): string => trim((string) ($group[$field] ?? '')),
                $matching
            ))));
            $slot[$field] = implode("\n", $values);
        }

        return $slot;
    }

    /**
     * @param  list<array<string, mixed>>  $signers
     * @return array<string, array<string, mixed>>
     */
    private function signatureAliases(array $signers): array
    {
        $byKey = collect($signers)->keyBy('key');
        $empty = [
            'role_line1' => '',
            'role_line2' => '',
            'name' => '',
            'image' => '',
            'enabled' => false,
        ];

        return [
            'schedule_maker' => $byKey->get('nguoi_lam_lich', $empty),
            'training_manager' => $byKey->get('kt_truong_phong', $empty),
            'principal' => $byKey->get('kt_hieu_truong', $empty),
        ];
    }
}
