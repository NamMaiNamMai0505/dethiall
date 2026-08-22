@php
    $settingValue = static fn (string $scope, string $key, mixed $default = null) =>
        data_get($settings->get($scope.'.'.$key), 'value', $default);

    $hubItems = match ($portal) {
        'dashboard' => [
            ['key' => 'dashboard-identity', 'icon' => 'bi-building', 'title' => 'Nhận diện cơ quan', 'description' => 'Tên cơ quan, đơn vị và dữ liệu nhận diện dùng chung.', 'meta' => '2 thiết lập'],
            ['key' => 'dashboard-document', 'icon' => 'bi-file-earmark-text', 'title' => 'Tiêu đề văn bản', 'description' => 'Quốc hiệu, tiêu ngữ và địa danh ký văn bản.', 'meta' => '3 thiết lập'],
            ['key' => 'dashboard-export', 'icon' => 'bi-file-earmark-arrow-down', 'title' => 'Mẫu xuất mặc định', 'description' => 'Định dạng, khổ giấy và hướng giấy khi tạo mẫu.', 'meta' => '3 thiết lập'],
            ['key' => 'academic', 'icon' => 'bi-calendar3', 'title' => 'Năm học dùng chung', 'description' => 'Quản lý danh mục năm học đồng bộ toàn hệ thống.', 'meta' => $academicYears->count().' năm học'],
        ],
        'lms' => [
            ['key' => 'lms-course', 'icon' => 'bi-mortarboard', 'title' => 'Khóa học', 'description' => 'Trạng thái mặc định khi khởi tạo khóa học mới.', 'meta' => '1 thiết lập'],
            ['key' => 'lms-assignments', 'icon' => 'bi-journal-check', 'title' => 'Bài tập', 'description' => 'Điểm, dung lượng tệp và chính sách nộp muộn.', 'meta' => '3 thiết lập'],
            ['key' => 'lms-exams', 'icon' => 'bi-ui-checks-grid', 'title' => 'Bài thi', 'description' => 'Thời lượng, số lượt, điểm đạt và đảo câu hỏi.', 'meta' => '4 thiết lập'],
            ['key' => 'lms-gradebook', 'icon' => 'bi-percent', 'title' => 'Công thức điểm LMS', 'description' => 'Trọng số bài tập, bài thi, chuyên cần và tiến độ.', 'meta' => '4 thiết lập'],
            ['key' => 'lms-notifications', 'icon' => 'bi-bell', 'title' => 'Thông báo', 'description' => 'Thiết lập thông báo trong quá trình dạy và học.', 'meta' => '1 thiết lập'],
            ['key' => 'academic', 'icon' => 'bi-calendar3', 'title' => 'Năm học dùng chung', 'description' => 'Quản lý danh mục năm học đồng bộ toàn hệ thống.', 'meta' => $academicYears->count().' năm học'],
        ],
        default => [
            ['key' => 'grades-scale', 'icon' => 'bi-speedometer', 'title' => 'Thang điểm & xếp loại', 'description' => 'Giới hạn điểm và các ngưỡng đánh giá kết quả.', 'meta' => '3 thiết lập'],
            ['key' => 'grades-rounding', 'icon' => 'bi-calculator', 'title' => 'Hiển thị & làm tròn', 'description' => 'Số thập phân và quy tắc làm tròn kết quả.', 'meta' => '2 thiết lập'],
            ['key' => 'grades-weights', 'icon' => 'bi-percent', 'title' => 'Trọng số điểm', 'description' => 'Tỷ lệ các thành phần dùng tính trung bình môn.', 'meta' => '4 thiết lập'],
            ['key' => 'academic', 'icon' => 'bi-calendar3', 'title' => 'Năm học dùng chung', 'description' => 'Quản lý danh mục năm học đồng bộ toàn hệ thống.', 'meta' => $academicYears->count().' năm học'],
        ],
    };

    $hubErrorMap = [
        'parent_organization_name' => 'dashboard-identity',
        'organization_name' => 'dashboard-identity',
        'national_heading' => 'dashboard-document',
        'national_motto' => 'dashboard-document',
        'document_location' => 'dashboard-document',
        'default_export_format' => 'dashboard-export',
        'default_page_size' => 'dashboard-export',
        'default_orientation' => 'dashboard-export',
        'default_course_status' => 'lms-course',
        'default_assignment_max_score' => 'lms-assignments',
        'submission_max_file_mb' => 'lms-assignments',
        'allow_late_by_default' => 'lms-assignments',
        'default_exam_duration_minutes' => 'lms-exams',
        'default_exam_attempts' => 'lms-exams',
        'default_exam_pass_score' => 'lms-exams',
        'shuffle_questions_by_default' => 'lms-exams',
        'notify_assignment_graded' => 'lms-notifications',
        'grade_weight_assignments' => 'lms-gradebook',
        'grade_weight_exams' => 'lms-gradebook',
        'grade_weight_attendance' => 'lms-gradebook',
        'grade_weight_progress' => 'lms-gradebook',
        'max_score' => 'grades-scale',
        'pass_score' => 'grades-scale',
        'excellent_score' => 'grades-scale',
        'decimal_places' => 'grades-rounding',
        'rounding_mode' => 'grades-rounding',
        'start_year' => 'academic',
        'starts_at' => 'academic',
        'ends_at' => 'academic',
        'is_current' => 'academic',
        'is_active' => 'academic',
    ];

    $validHubKeys = collect($hubItems)->pluck('key');
    $requestedHubSection = request()->query('section');
    $defaultHubSection = $validHubKeys->contains($requestedHubSection)
        ? $requestedHubSection
        : data_get($hubItems, '0.key');

    foreach ($errors->keys() as $errorKey) {
        $errorSection = str_starts_with($errorKey, 'weight_')
            ? 'grades-weights'
            : ($hubErrorMap[$errorKey] ?? null);

        if ($errorSection && $validHubKeys->contains($errorSection)) {
            $defaultHubSection = $errorSection;
            break;
        }
    }
@endphp

<div class="ss-context">
    <div class="ss-context-main">
        <span class="ss-context-icon"><i class="bi {{ $portalIcon }}"></i></span>
        <div>
            <h2>{{ $portalLabel }}</h2>
            <p>{{ $portalDescription }}</p>
        </div>
    </div>
    <span class="ss-db-chip"><i class="bi bi-database-check"></i> Đồng bộ CSDL</span>
</div>

@unless($canEdit)
    <div class="ss-readonly">
        <i class="bi bi-eye"></i>
        <span>Bạn đang xem cấu hình ở chế độ chỉ đọc. Chỉ quản trị viên hệ thống có thể thay đổi dữ liệu dùng chung.</span>
    </div>
@endunless

<div class="ss-settings-hub"
     data-settings-hub-root
     data-default-section="{{ $defaultHubSection }}">
    @include('system-settings::partials.portal-settings')

<div class="ss-layout {{ $defaultHubSection === 'academic' ? 'is-active' : '' }}"
     data-settings-hub-panel="academic"
     data-settings-academic-panel
     role="tabpanel"
     aria-label="Năm học dùng chung">
    <section class="ss-card ss-card--academic">
        <header class="ss-card-head">
            <div class="ss-section-title">
                <span class="ss-section-icon"><i class="bi bi-calendar3"></i></span>
                <div>
                    <h2>Danh mục năm học dùng chung</h2>
                    <p>Nhập năm bắt đầu, hệ thống tự sinh mã như 2028-2029 và dùng chung giữa các phân hệ.</p>
                </div>
            </div>
        </header>

        @if($canEdit)
            <form action="{{ route('settings.academic-years.store') }}" method="POST" class="ss-add-form">
                @csrf
                <div class="ss-field">
                    <label for="settings-start-year-{{ $portal }}">Năm bắt đầu</label>
                    <input id="settings-start-year-{{ $portal }}" type="number" name="start_year"
                           min="2000" max="2200" required value="{{ old('start_year', now()->year + 1) }}"
                           placeholder="2028">
                </div>
                <div class="ss-field">
                    <label for="settings-start-date-{{ $portal }}">Ngày bắt đầu</label>
                    <input id="settings-start-date-{{ $portal }}" type="date" name="starts_at"
                           value="{{ old('starts_at') }}">
                </div>
                <div class="ss-field">
                    <label for="settings-end-date-{{ $portal }}">Ngày kết thúc</label>
                    <input id="settings-end-date-{{ $portal }}" type="date" name="ends_at"
                           value="{{ old('ends_at') }}">
                </div>
                <label class="ss-checkbox">
                    <input type="checkbox" name="is_current" value="1" @checked(old('is_current'))>
                    <span>Đặt hiện hành</span>
                </label>
                <button type="submit" class="ss-btn ss-btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Thêm năm học</span>
                </button>
                <input type="hidden" name="is_active" value="1">
            </form>
        @endif

        <div class="ss-table-wrap">
            <table class="ss-table">
                <thead>
                    <tr>
                        <th>Năm học</th>
                        <th>Khoảng thời gian</th>
                        <th style="text-align:center">Trạng thái</th>
                        @if($canEdit)<th style="text-align:right">Thao tác</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($academicYears as $academicYear)
                        <tr>
                            <td>
                                <span class="ss-year-code">{{ $academicYear->code }}</span>
                                @if($academicYear->is_current)
                                    <span class="ss-current-chip">Hiện hành</span>
                                @endif
                            </td>
                            <td>
                                {{ $academicYear->starts_at?->format('d/m/Y') ?? '—' }}
                                <span aria-hidden="true"> → </span>
                                {{ $academicYear->ends_at?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td style="text-align:center">
                                <span class="ss-status {{ $academicYear->is_active ? 'ss-status--active' : 'ss-status--inactive' }}">
                                    {{ $academicYear->is_active ? 'Đang dùng' : 'Đã ẩn' }}
                                </span>
                            </td>
                            @if($canEdit)
                                <td>
                                    <div class="ss-actions">
                                        @unless($academicYear->is_current)
                                            <form method="POST" action="{{ route('settings.academic-years.current', $academicYear) }}">
                                                @csrf
                                                <button type="submit" class="ss-btn ss-btn-soft">Đặt hiện hành</button>
                                            </form>
                                        @endunless
                                        <form method="POST" action="{{ route('settings.academic-years.update', $academicYear) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="starts_at" value="{{ $academicYear->starts_at?->format('Y-m-d') }}">
                                            <input type="hidden" name="ends_at" value="{{ $academicYear->ends_at?->format('Y-m-d') }}">
                                            <input type="hidden" name="is_active" value="{{ $academicYear->is_active ? 0 : 1 }}">
                                            <button type="submit" class="ss-btn ss-btn-neutral">
                                                {{ $academicYear->is_active ? 'Ẩn' : 'Bật' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit ? 4 : 3 }}" class="ss-empty">
                                <i class="bi bi-calendar-x"></i> Chưa có năm học nào trong danh mục.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>
</div>
