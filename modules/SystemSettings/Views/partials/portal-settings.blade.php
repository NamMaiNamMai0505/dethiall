<div class="ss-portal-heading">
    <div>
        <span class="ss-eyebrow"><i class="bi bi-grid-1x2"></i> Trung tâm cài đặt</span>
        <h2>Chọn khu vực cần cấu hình</h2>
        <p>Mỗi ô là một nhóm chức năng. Bấm để mở và chỉnh sửa ngay bên dưới.</p>
    </div>
    <span class="ss-setting-count">
        {{ count($hubItems) }} khu vực
    </span>
</div>

<div class="ss-hub-grid" role="tablist" aria-label="Các khu vực cài đặt {{ $portalShortLabel }}">
    @foreach($hubItems as $hubItem)
        <button type="button"
                class="ss-hub-tile {{ $defaultHubSection === $hubItem['key'] ? 'is-active' : '' }}"
                data-settings-hub-trigger="{{ $hubItem['key'] }}"
                role="tab"
                aria-selected="{{ $defaultHubSection === $hubItem['key'] ? 'true' : 'false' }}">
            <span class="ss-hub-tile-icon"><i class="bi {{ $hubItem['icon'] }}"></i></span>
            <span class="ss-hub-tile-content">
                <strong>{{ $hubItem['title'] }}</strong>
                <small>{{ $hubItem['description'] }}</small>
                <span>{{ $hubItem['meta'] }}</span>
            </span>
            <i class="bi bi-arrow-right-short ss-hub-arrow" aria-hidden="true"></i>
        </button>
    @endforeach
</div>

<form method="POST"
      action="{{ route('settings.general.update', $portal) }}"
      class="ss-portal-form {{ $defaultHubSection === 'academic' ? 'is-hub-hidden' : '' }}"
      data-settings-general-form
      novalidate>
    @csrf
    @method('PUT')

    <div class="ss-settings-panels">
        @if($portal === 'dashboard')
            <section class="ss-card ss-setting-card {{ $defaultHubSection === 'dashboard-identity' ? 'is-active' : '' }}"
                     data-settings-hub-panel="dashboard-identity"
                     role="tabpanel">
                <header class="ss-card-head">
                    <div class="ss-section-title">
                        <span class="ss-section-icon"><i class="bi bi-building"></i></span>
                        <div>
                            <h2>Nhận diện cơ quan</h2>
                            <p>Điền tự động vào header và placeholder mẫu xuất.</p>
                        </div>
                    </div>
                </header>
                <div class="ss-side-body ss-form-stack">
                    <div class="ss-field">
                        <label for="parent-organization-name">Cơ quan cấp trên</label>
                        <input id="parent-organization-name" name="parent_organization_name" required
                               @disabled(!$canEdit)
                               value="{{ old('parent_organization_name', $settingValue('shared', 'parent_organization_name', 'TỔNG CỤC HẬU CẦN - KỸ THUẬT')) }}">
                    </div>
                    <div class="ss-field">
                        <label for="organization-name">Tên đơn vị</label>
                        <input id="organization-name" name="organization_name" required
                               @disabled(!$canEdit)
                               value="{{ old('organization_name', $settingValue('shared', 'organization_name', 'TRƯỜNG CAO ĐẲNG HẬU CẦN 2')) }}">
                    </div>
                </div>
            </section>

            <section class="ss-card ss-setting-card {{ $defaultHubSection === 'dashboard-document' ? 'is-active' : '' }}"
                     data-settings-hub-panel="dashboard-document"
                     role="tabpanel">
                <header class="ss-card-head">
                    <div class="ss-section-title">
                        <span class="ss-section-icon"><i class="bi bi-file-earmark-text"></i></span>
                        <div>
                            <h2>Tiêu đề văn bản</h2>
                            <p>Khối quốc hiệu, tiêu ngữ và địa danh mặc định.</p>
                        </div>
                    </div>
                </header>
                <div class="ss-side-body ss-form-stack">
                    <div class="ss-field">
                        <label for="national-heading">Quốc hiệu</label>
                        <input id="national-heading" name="national_heading" required @disabled(!$canEdit)
                               value="{{ old('national_heading', $settingValue('shared', 'national_heading', 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM')) }}">
                    </div>
                    <div class="ss-field">
                        <label for="national-motto">Tiêu ngữ</label>
                        <input id="national-motto" name="national_motto" required @disabled(!$canEdit)
                               value="{{ old('national_motto', $settingValue('shared', 'national_motto', 'Độc lập - Tự do - Hạnh phúc')) }}">
                    </div>
                    <div class="ss-field">
                        <label for="document-location">Địa danh ký văn bản</label>
                        <input id="document-location" name="document_location" required @disabled(!$canEdit)
                               value="{{ old('document_location', $settingValue('shared', 'document_location', 'Thành phố Hồ Chí Minh')) }}">
                    </div>
                </div>
            </section>

            <section class="ss-card ss-setting-card {{ $defaultHubSection === 'dashboard-export' ? 'is-active' : '' }}"
                     data-settings-hub-panel="dashboard-export"
                     role="tabpanel">
                <header class="ss-card-head">
                    <div class="ss-section-title">
                        <span class="ss-section-icon"><i class="bi bi-file-earmark-arrow-down"></i></span>
                        <div>
                            <h2>Mẫu xuất mặc định</h2>
                            <p>Áp dụng khi tạo tài liệu mới trong Template Builder.</p>
                        </div>
                    </div>
                </header>
                <div class="ss-side-body ss-form-stack">
                    <div class="ss-field">
                        <label for="default-export-format">Định dạng tạo mẫu</label>
                        <select id="default-export-format" name="default_export_format" data-native-select @disabled(!$canEdit)>
                            <option value="excel" @selected(old('default_export_format', $settingValue('shared', 'default_export_format', 'excel')) === 'excel')>Excel</option>
                            <option value="word" @selected(old('default_export_format', $settingValue('shared', 'default_export_format', 'excel')) === 'word')>Word</option>
                        </select>
                    </div>
                    <div class="ss-two-cols">
                        <div class="ss-field">
                            <label for="default-page-size">Khổ giấy</label>
                            <select id="default-page-size" name="default_page_size" data-native-select @disabled(!$canEdit)>
                                @foreach(['A4', 'A3', 'Letter'] as $size)
                                    <option value="{{ $size }}" @selected(old('default_page_size', $settingValue('shared', 'default_page_size', 'A4')) === $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ss-field">
                            <label for="default-orientation">Hướng giấy</label>
                            <select id="default-orientation" name="default_orientation" data-native-select @disabled(!$canEdit)>
                                <option value="landscape" @selected(old('default_orientation', $settingValue('shared', 'default_orientation', 'landscape')) === 'landscape')>Ngang</option>
                                <option value="portrait" @selected(old('default_orientation', $settingValue('shared', 'default_orientation', 'landscape')) === 'portrait')>Dọc</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>
        @elseif($portal === 'lms')
            <section class="ss-card ss-setting-card {{ $defaultHubSection === 'lms-course' ? 'is-active' : '' }}"
                     data-settings-hub-panel="lms-course"
                     role="tabpanel">
                <header class="ss-card-head">
                    <div class="ss-section-title">
                        <span class="ss-section-icon"><i class="bi bi-mortarboard"></i></span>
                        <div>
                            <h2>Khóa học</h2>
                            <p>Mặc định cho Wizard tạo khóa LMS.</p>
                        </div>
                    </div>
                </header>
                <div class="ss-side-body ss-form-stack">
                    <div class="ss-field">
                        <label for="default-course-status">Trạng thái khóa học mới</label>
                        <select id="default-course-status" name="default_course_status" data-native-select @disabled(!$canEdit)>
                            <option value="draft" @selected(old('default_course_status', $settingValue('lms', 'default_course_status', 'draft')) === 'draft')>Bản nháp</option>
                            <option value="published" @selected(old('default_course_status', $settingValue('lms', 'default_course_status', 'draft')) === 'published')>Công bố ngay</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="ss-card ss-setting-card {{ $defaultHubSection === 'lms-assignments' ? 'is-active' : '' }}"
                     data-settings-hub-panel="lms-assignments"
                     role="tabpanel">
                <header class="ss-card-head">
                    <div class="ss-section-title">
                        <span class="ss-section-icon"><i class="bi bi-journal-check"></i></span>
                        <div>
                            <h2>Bài tập</h2>
                            <p>Điểm, dung lượng file và quy tắc nộp muộn.</p>
                        </div>
                    </div>
                </header>
                <div class="ss-side-body ss-form-stack">
                    <div class="ss-two-cols">
                        <div class="ss-field">
                            <label for="assignment-max-score">Điểm tối đa</label>
                            <input id="assignment-max-score" type="number" name="default_assignment_max_score"
                                   min="1" max="1000" step="0.1" required @disabled(!$canEdit)
                                   value="{{ old('default_assignment_max_score', $settingValue('lms', 'default_assignment_max_score', 10)) }}">
                        </div>
                        <div class="ss-field">
                            <label for="submission-max-file">File tối đa (MB)</label>
                            <input id="submission-max-file" type="number" name="submission_max_file_mb"
                                   min="1" max="500" required @disabled(!$canEdit)
                                   value="{{ old('submission_max_file_mb', $settingValue('lms', 'submission_max_file_mb', 50)) }}">
                        </div>
                    </div>
                    <input type="hidden" name="allow_late_by_default" value="0">
                    <label class="ss-switch">
                        <input type="checkbox" name="allow_late_by_default" value="1" @disabled(!$canEdit)
                               @checked((bool) old('allow_late_by_default', $settingValue('lms', 'allow_late_by_default', false)))>
                        <span class="ss-switch-track"><span></span></span>
                        <span><strong>Cho nộp muộn mặc định</strong><small>Giảng viên vẫn có thể đổi trên từng bài.</small></span>
                    </label>
                </div>
            </section>

            <section class="ss-card ss-setting-card {{ $defaultHubSection === 'lms-exams' ? 'is-active' : '' }}"
                     data-settings-hub-panel="lms-exams"
                     role="tabpanel">
                <header class="ss-card-head">
                    <div class="ss-section-title">
                        <span class="ss-section-icon"><i class="bi bi-ui-checks-grid"></i></span>
                        <div>
                            <h2>Bài thi</h2>
                            <p>Giá trị khởi tạo cho bài thi trực tuyến.</p>
                        </div>
                    </div>
                </header>
                <div class="ss-side-body ss-form-stack">
                    <div class="ss-two-cols">
                        <div class="ss-field">
                            <label for="exam-duration">Thời lượng (phút)</label>
                            <input id="exam-duration" type="number" name="default_exam_duration_minutes"
                                   min="5" max="480" required @disabled(!$canEdit)
                                   value="{{ old('default_exam_duration_minutes', $settingValue('lms', 'default_exam_duration_minutes', 45)) }}">
                        </div>
                        <div class="ss-field">
                            <label for="exam-attempts">Số lượt làm</label>
                            <input id="exam-attempts" type="number" name="default_exam_attempts"
                                   min="1" max="20" required @disabled(!$canEdit)
                                   value="{{ old('default_exam_attempts', $settingValue('lms', 'default_exam_attempts', 1)) }}">
                        </div>
                    </div>
                    <div class="ss-field">
                        <label for="exam-pass-score">Điểm đạt mặc định</label>
                        <input id="exam-pass-score" type="number" name="default_exam_pass_score"
                               min="0" max="1000" step="0.1" required @disabled(!$canEdit)
                               value="{{ old('default_exam_pass_score', $settingValue('lms', 'default_exam_pass_score', 5)) }}">
                    </div>
                    <input type="hidden" name="shuffle_questions_by_default" value="0">
                    <label class="ss-switch">
                        <input type="checkbox" name="shuffle_questions_by_default" value="1" @disabled(!$canEdit)
                               @checked((bool) old('shuffle_questions_by_default', $settingValue('lms', 'shuffle_questions_by_default', true)))>
                        <span class="ss-switch-track"><span></span></span>
                        <span><strong>Đảo thứ tự câu hỏi</strong><small>Bật sẵn khi tạo bài thi mới.</small></span>
                    </label>
                </div>
            </section>

            <section class="ss-card ss-setting-card {{ $defaultHubSection === 'lms-notifications' ? 'is-active' : '' }}"
                     data-settings-hub-panel="lms-notifications"
                     role="tabpanel">
                <header class="ss-card-head">
                    <div class="ss-section-title">
                        <span class="ss-section-icon"><i class="bi bi-bell"></i></span>
                        <div>
                            <h2>Thông báo</h2>
                            <p>Kiểm soát thông báo phát sinh từ nghiệp vụ LMS.</p>
                        </div>
                    </div>
                </header>
                <div class="ss-side-body">
                    <input type="hidden" name="notify_assignment_graded" value="0">
                    <label class="ss-switch">
                        <input type="checkbox" name="notify_assignment_graded" value="1" @disabled(!$canEdit)
                               @checked((bool) old('notify_assignment_graded', $settingValue('lms', 'notify_assignment_graded', true)))>
                        <span class="ss-switch-track"><span></span></span>
                        <span><strong>Báo khi bài tập được chấm</strong><small>Gửi chuông hệ thống và email hàng đợi cho học viên.</small></span>
                    </label>
                </div>
            </section>

            <section class="ss-card ss-setting-card {{ $defaultHubSection === 'lms-gradebook' ? 'is-active' : '' }}"
                     data-settings-hub-panel="lms-gradebook"
                     role="tabpanel">
                <header class="ss-card-head">
                    <div class="ss-section-title">
                        <span class="ss-section-icon"><i class="bi bi-percent"></i></span>
                        <div>
                            <h2>Công thức điểm LMS</h2>
                            <p>Tổng bốn trọng số phải bằng 100%. Công thức này không tự ghi đè bảng điểm đã duyệt.</p>
                        </div>
                    </div>
                </header>
                <div class="ss-side-body ss-form-stack">
                    <div class="ss-two-cols">
                        @foreach([
                            'grade_weight_assignments' => ['Bài tập', 40],
                            'grade_weight_exams' => ['Bài thi', 40],
                            'grade_weight_attendance' => ['Chuyên cần', 10],
                            'grade_weight_progress' => ['Tiến độ học', 10],
                        ] as $weightKey => [$weightLabel, $weightDefault])
                            <div class="ss-field">
                                <label for="{{ str_replace('_', '-', $weightKey) }}">{{ $weightLabel }} (%)</label>
                                <input id="{{ str_replace('_', '-', $weightKey) }}" type="number" name="{{ $weightKey }}"
                                       min="0" max="100" step="0.1" required @disabled(!$canEdit)
                                       value="{{ old($weightKey, $settingValue('lms', $weightKey, $weightDefault)) }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @else
            <section class="ss-card ss-setting-card {{ $defaultHubSection === 'grades-scale' ? 'is-active' : '' }}"
                     data-settings-hub-panel="grades-scale"
                     role="tabpanel">
                <header class="ss-card-head">
                    <div class="ss-section-title">
                        <span class="ss-section-icon"><i class="bi bi-speedometer"></i></span>
                        <div>
                            <h2>Thang điểm & xếp loại</h2>
                            <p>Giới hạn nhập điểm và ngưỡng kết quả.</p>
                        </div>
                    </div>
                </header>
                <div class="ss-side-body ss-form-stack">
                    <div class="ss-three-cols">
                        <div class="ss-field">
                            <label for="grades-max-score">Thang điểm</label>
                            <input id="grades-max-score" type="number" step="0.1" min="1" max="100"
                                   name="max_score" required @disabled(!$canEdit)
                                   value="{{ old('max_score', $settingValue('grades', 'max_score', 10)) }}">
                        </div>
                        <div class="ss-field">
                            <label for="grades-pass-score">Điểm đạt</label>
                            <input id="grades-pass-score" type="number" step="0.1" min="0"
                                   name="pass_score" required @disabled(!$canEdit)
                                   value="{{ old('pass_score', $settingValue('grades', 'pass_score', 5)) }}">
                        </div>
                        <div class="ss-field">
                            <label for="grades-excellent-score">Ngưỡng Giỏi</label>
                            <input id="grades-excellent-score" type="number" step="0.1" min="0"
                                   name="excellent_score" required @disabled(!$canEdit)
                                   value="{{ old('excellent_score', $settingValue('grades', 'excellent_score', 8)) }}">
                        </div>
                    </div>
                </div>
            </section>

            <section class="ss-card ss-setting-card {{ $defaultHubSection === 'grades-rounding' ? 'is-active' : '' }}"
                     data-settings-hub-panel="grades-rounding"
                     role="tabpanel">
                <header class="ss-card-head">
                    <div class="ss-section-title">
                        <span class="ss-section-icon"><i class="bi bi-calculator"></i></span>
                        <div>
                            <h2>Hiển thị & làm tròn</h2>
                            <p>Áp dụng khi lưu, tính trung bình và xuất kết quả.</p>
                        </div>
                    </div>
                </header>
                <div class="ss-side-body ss-form-stack">
                    <div class="ss-field">
                        <label for="grades-decimal-places">Số chữ số thập phân</label>
                        <select id="grades-decimal-places" name="decimal_places" data-native-select @disabled(!$canEdit)>
                            @foreach([0, 1, 2] as $places)
                                <option value="{{ $places }}" @selected((int) old('decimal_places', $settingValue('grades', 'decimal_places', 1)) === $places)>
                                    {{ $places }} chữ số
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ss-field">
                        <label for="grades-rounding-mode">Quy tắc làm tròn</label>
                        <select id="grades-rounding-mode" name="rounding_mode" data-native-select @disabled(!$canEdit)>
                            <option value="half_up" @selected(old('rounding_mode', $settingValue('grades', 'rounding_mode', 'half_up')) === 'half_up')>Từ 5 làm tròn lên</option>
                            <option value="half_down" @selected(old('rounding_mode', $settingValue('grades', 'rounding_mode', 'half_up')) === 'half_down')>Từ 5 làm tròn xuống</option>
                            <option value="half_even" @selected(old('rounding_mode', $settingValue('grades', 'rounding_mode', 'half_up')) === 'half_even')>Làm tròn về số chẵn gần nhất</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="ss-card ss-setting-card {{ $defaultHubSection === 'grades-weights' ? 'is-active' : '' }}"
                     data-settings-hub-panel="grades-weights"
                     role="tabpanel">
                <header class="ss-card-head">
                    <div class="ss-section-title">
                        <span class="ss-section-icon"><i class="bi bi-percent"></i></span>
                        <div>
                            <h2>Trọng số trung bình môn</h2>
                            <p>Tổng bốn thành phần bắt buộc bằng 100%.</p>
                        </div>
                    </div>
                </header>
                <div class="ss-side-body">
                    <div class="ss-weight-grid">
                        @foreach([
                            'weight_oral_15' => ['Kiểm tra 15 phút', 10],
                            'weight_period_1' => ['Kiểm tra 1 tiết', 20],
                            'weight_midterm' => ['Giữa kỳ', 30],
                            'weight_final' => ['Điểm thi', 40],
                        ] as $key => [$label, $default])
                            <div class="ss-field">
                                <label for="grades-{{ str_replace('_', '-', $key) }}">{{ $label }} (%)</label>
                                <input id="grades-{{ str_replace('_', '-', $key) }}" type="number" step="0.1"
                                       min="0" max="100" name="{{ $key }}" required @disabled(!$canEdit)
                                       value="{{ old($key, $settingValue('grades', $key, $default)) }}">
                            </div>
                        @endforeach
                    </div>
                    <div class="ss-weight-total" data-weight-total>
                        <i class="bi bi-check-circle"></i>
                        <span>Tổng trọng số: <strong>100%</strong></span>
                    </div>
                </div>
            </section>
        @endif
    </div>

    <div class="ss-save-bar">
        <div class="ss-connection">
            <i class="bi bi-diagram-3"></i>
            @if($portal === 'dashboard')
                <span>Nhận diện cơ quan và mặc định mẫu xuất được dùng chung khi Dashboard, LMS hoặc Quản lý điểm tạo tài liệu.</span>
            @else
                <span>Năm học dùng chung giữa ba portal; các thiết lập bên trên chỉ tác động đến {{ $portalShortLabel }}.</span>
            @endif
        </div>
        @if($canEdit)
            <button type="submit" class="ss-btn ss-btn-primary">
                <i class="bi bi-floppy"></i>
                <span>Lưu toàn bộ cài đặt {{ $portalShortLabel }}</span>
            </button>
        @endif
    </div>
</form>

@push('scripts')
    <script>
        (function () {
            function updateWeightTotal(root) {
                if (!root || !root.closest('.system-settings--grades')) return;

                const fields = ['weight_oral_15', 'weight_period_1', 'weight_midterm', 'weight_final'];
                const total = fields.reduce(function (sum, name) {
                    return sum + (Number(root.querySelector('[name="' + name + '"]')?.value) || 0);
                }, 0);
                const box = root.querySelector('[data-weight-total]');

                if (!box) return;

                const valid = Math.abs(total - 100) <= 0.001;
                box.querySelector('strong').textContent = total.toLocaleString('vi-VN', {
                    maximumFractionDigits: 2
                }) + '%';
                box.classList.toggle('is-invalid', !valid);
                box.querySelector('i').className = valid
                    ? 'bi bi-check-circle'
                    : 'bi bi-exclamation-triangle';
            }

            function activateHubSection(root, section, updateUrl) {
                if (!root) return;

                const trigger = root.querySelector('[data-settings-hub-trigger="' + section + '"]');
                if (!trigger) return;

                root.querySelectorAll('[data-settings-hub-trigger]').forEach(function (item) {
                    const active = item === trigger;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                    item.setAttribute('tabindex', active ? '0' : '-1');
                });

                root.querySelectorAll('.ss-settings-panels > [data-settings-hub-panel]').forEach(function (panel) {
                    const active = panel.dataset.settingsHubPanel === section;
                    panel.classList.toggle('is-active', active);
                    panel.setAttribute('aria-hidden', active ? 'false' : 'true');
                });

                const academicPanel = root.querySelector('[data-settings-academic-panel]');
                const academicActive = section === 'academic';
                if (academicPanel) {
                    academicPanel.classList.toggle('is-active', academicActive);
                    academicPanel.setAttribute('aria-hidden', academicActive ? 'false' : 'true');
                }

                const generalForm = root.querySelector('[data-settings-general-form]');
                generalForm?.classList.toggle('is-hub-hidden', academicActive);
                root.dataset.activeSection = section;
                updateWeightTotal(root);

                if (updateUrl && window.history?.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('section', section);
                    window.history.replaceState(window.history.state, '', url);
                }
            }

            function bootSettingsHubs(scope) {
                (scope || document).querySelectorAll('[data-settings-hub-root]').forEach(function (root) {
                    const initialSection = root.dataset.defaultSection
                        || root.querySelector('[data-settings-hub-trigger]')?.dataset.settingsHubTrigger;

                    activateHubSection(root, initialSection, false);
                    root.dataset.hubReady = '1';
                });
            }

            if (!window.__systemSettingsHubEventsBound) {
                window.__systemSettingsHubEventsBound = true;

                document.addEventListener('click', function (event) {
                    const trigger = event.target.closest('[data-settings-hub-trigger]');
                    if (!trigger) return;

                    activateHubSection(
                        trigger.closest('[data-settings-hub-root]'),
                        trigger.dataset.settingsHubTrigger,
                        true
                    );
                });

                document.addEventListener('keydown', function (event) {
                    const trigger = event.target.closest('[data-settings-hub-trigger]');
                    if (!trigger || !['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;

                    const root = trigger.closest('[data-settings-hub-root]');
                    const triggers = Array.from(root.querySelectorAll('[data-settings-hub-trigger]'));
                    const currentIndex = triggers.indexOf(trigger);
                    const delta = ['ArrowRight', 'ArrowDown'].includes(event.key) ? 1 : -1;
                    const next = triggers[(currentIndex + delta + triggers.length) % triggers.length];

                    event.preventDefault();
                    next.focus();
                    next.click();
                });

                document.addEventListener('input', function (event) {
                    if (!event.target.name?.startsWith('weight_')) return;
                    updateWeightTotal(event.target.closest('[data-settings-hub-root]'));
                });

                document.addEventListener('submit', function (event) {
                    const form = event.target.closest('[data-settings-general-form]');
                    if (!form || form.checkValidity()) return;

                    event.preventDefault();
                    const invalidField = form.querySelector(':invalid');
                    const panel = invalidField?.closest('[data-settings-hub-panel]');
                    const root = form.closest('[data-settings-hub-root]');

                    if (panel) {
                        activateHubSection(root, panel.dataset.settingsHubPanel, true);
                    }

                    invalidField?.focus();
                    invalidField?.reportValidity();
                });

                document.addEventListener('DOMContentLoaded', function () {
                    bootSettingsHubs(document);
                });
                document.addEventListener('turbo:load', function () {
                    bootSettingsHubs(document);
                });
            }

            bootSettingsHubs(document);
        })();
    </script>
@endpush
