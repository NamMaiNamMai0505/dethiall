<?php

namespace App\Support;

/**
 * Danh mục ứng dụng của toàn hệ thống — nguồn duy nhất cho ma trận phân quyền.
 *
 * Cấu trúc: Phân hệ → Ứng dụng → Hành động (Xem / Thêm / Sửa / Xóa / Duyệt / Xuất).
 *
 * Mỗi hành động ánh xạ sang danh sách hậu tố permission thật đang dùng trong
 * code, nên bảng ma trận hiển thị theo ngôn ngữ nghiệp vụ mà không phải đổi tên
 * permission nào. Tick một ô = cấp trọn danh sách permission của ô đó.
 */
final class ApplicationRegistry
{
    public const ACTION_VIEW = 'view';

    public const ACTION_CREATE = 'create';

    public const ACTION_EDIT = 'edit';

    public const ACTION_DELETE = 'delete';

    public const ACTION_APPROVE = 'approve';

    public const ACTION_EXPORT = 'export';

    public const ACTION_SUBMIT = 'submit';

    public const ACTION_IMPORT = 'import';

    public const ACTION_BANK = 'bank';

    public const ACTION_DRAW = 'draw';

    /**
     * Các prefix từng có permission gộp `<prefix>.manage` trước khi tách chi tiết.
     * Dùng để migrate role cũ; không thêm prefix mới vào đây.
     *
     * @var list<string>
     */
    private const LEGACY_MANAGE_PREFIXES = [
        'standard-hours.object-types',
        'standard-hours.positions',
        'standard-hours.departments',
        'standard-hours.department-overtime',
        'standard-hours.norm-reductions',
        'standard-hours.conversion-categories',
        'standard-hours.research-categories',
        'standard-hours.conversion-records',
        'standard-hours.research-records',
        'standard-hours.external-activities',
        'standard-hours.hour-exchanges',
    ];

    /** Bộ hành động CRUD chuẩn của các module dùng ModuleBaseController. */
    private const CRUD_CORE = [
        self::ACTION_VIEW => ['index', 'show'],
        self::ACTION_CREATE => ['create'],
        self::ACTION_EDIT => ['edit'],
        self::ACTION_DELETE => ['delete'],
    ];

    /** Bộ hành động CRUD của các ứng dụng Giờ chuẩn GV (prefix riêng). */
    private const CRUD_STANDARD_HOURS = [
        self::ACTION_VIEW => ['view'],
        self::ACTION_CREATE => ['create'],
        self::ACTION_EDIT => ['edit'],
        self::ACTION_DELETE => ['delete'],
    ];

    /** Bộ hành động CRUD của các ứng dụng con trong LMS. */
    private const CRUD_LMS = self::CRUD_STANDARD_HOURS;

    /** Bộ hành động CRUD của các ứng dụng con trong Quản lý điểm. */
    private const CRUD_GRADES = self::CRUD_STANDARD_HOURS;

    /** @return array<string, string> */
    public static function actionLabels(): array
    {
        return [
            self::ACTION_VIEW => 'Xem',
            self::ACTION_CREATE => 'Thêm',
            self::ACTION_EDIT => 'Sửa',
            self::ACTION_DELETE => 'Xóa',
            self::ACTION_APPROVE => 'Duyệt',
            self::ACTION_EXPORT => 'Xuất',
            self::ACTION_IMPORT => 'Nhập dữ liệu',
        ];
    }

    /** Thứ tự cột trong ma trận phân quyền. @return list<string> */
    public static function actionOrder(): array
    {
        return array_keys(self::actionLabels());
    }

    public static function actionLabel(string $action): string
    {
        return self::actionLabels()[$action] ?? ucfirst($action);
    }

    /**
     * Phân hệ → ứng dụng.
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     applications: list<array{key: string, label: string, permission: string, actions: array<string, list<string>>, note?: string}>
     * }>
     */
    public static function subsystems(): array
    {
        $subsystems = [
            [
                'key' => 'training',
                'label' => 'Lịch đào tạo',
                'applications' => [
                    self::app('training-schedules', 'Tạo lịch đào tạo', self::CRUD_CORE),
                    self::app('schedule-details', 'Phân chia lịch học', self::CRUD_CORE),
                    self::app('teaching-assignments', 'Phân công giảng dạy', self::CRUD_CORE),
                    self::app('specializations', 'Ngành đào tạo', self::CRUD_CORE),
                    self::app('subjects', 'Môn học', self::CRUD_CORE),
                    self::app('subject-lessons', 'Bài học (khung CTĐT)', self::CRUD_CORE),
                    self::app('classes', 'Lớp học', self::CRUD_CORE),
                ],
            ],
            [
                'key' => 'standard-hours',
                'label' => 'Giờ chuẩn GV',
                'applications' => self::standardHoursApplications(),
            ],
            [
                'key' => 'lms',
                'label' => 'LMS',
                'applications' => self::lmsApplications(),
            ],
            [
                'key' => 'grades',
                'label' => 'Quản lý điểm',
                'applications' => self::gradeApplications(),
            ],
            [
                'key' => 'essay-exams',
                'label' => 'Đề thi tự luận',
                'applications' => [
                    self::app('essay-exams', 'Phân hệ đề thi tự luận', [
                        self::ACTION_VIEW => ['index', 'mine', 'show'],
                    ]),
                    self::app('essay-exams.authoring', 'Soạn đề', self::CRUD_CORE),
                    self::app('essay-exams.import', 'Import đề', self::CRUD_CORE),
                    self::app('essay-exams.submission', 'Gửi đề đi duyệt', [
                        self::ACTION_VIEW => ['view'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                    ]),
                    self::app('essay-exams.approval', 'Duyệt đề', [
                        self::ACTION_VIEW => ['index'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                        self::ACTION_APPROVE => ['approve'],
                    ]),
                    self::app('essay-exams.bank', 'Ngân hàng đề', [
                        self::ACTION_VIEW => ['index'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                        self::ACTION_APPROVE => ['approve'],
                        self::ACTION_EXPORT => ['export'],
                        self::ACTION_IMPORT => ['import'],
                    ]),
                    self::app('essay-exams.draw', 'Rút đề', [
                        self::ACTION_VIEW => ['index'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                        self::ACTION_EXPORT => ['export'],
                    ]),
                ],
            ],
            [
                'key' => 'inventory-exam',
                'label' => 'Vật tư và tổ chức thi',
                'applications' => [
                    self::app('exam-organization', 'Tổ chức và quản lý thi', [
                        self::ACTION_VIEW => ['index'],
                        self::ACTION_CREATE => ['plan'],
                        self::ACTION_EDIT => ['assignment', 'execution', 'grading'],
                    ]),
                    self::app('inventory.access', 'Truy cập quản lý vật tư', [
                        self::ACTION_VIEW => ['index', 'show'],
                    ]),
                    self::app('inventory.materials', 'Danh mục vật tư', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                        self::ACTION_IMPORT => ['import'],
                    ]),
                    self::app('inventory.assets', 'Cập nhật vật tư trong đơn vị', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                        self::ACTION_IMPORT => ['import'],
                    ]),
                    self::app('inventory.warehouses', 'Kho vật tư', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                    ]),
                    self::app('inventory.proposals', 'Đề xuất vật tư', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_APPROVE => ['approve'],
                        self::ACTION_EXPORT => ['export'],
                    ]),
                    self::app('inventory.transfers', 'Điều động vật tư', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_APPROVE => ['approve'],
                        self::ACTION_EXPORT => ['export'],
                    ]),
                    self::app('inventory.repairs', 'Phân công / sửa chữa vật tư', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                    ]),
                    self::app('inventory.reports', 'Báo cáo vật tư', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_EXPORT => ['export'],
                    ]),
                    self::app('inventory.logs', 'Nhật ký vật tư', [
                        self::ACTION_VIEW => ['index', 'show'],
                    ]),
                    self::app('inventory.templates', 'Mẫu biểu vật tư', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                        self::ACTION_EXPORT => ['export'],
                    ]),
                    self::app('inventory', 'Quản lý vật tư (tương thích quyền cũ)', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                        self::ACTION_APPROVE => ['approve'],
                        self::ACTION_EXPORT => ['export'],
                        self::ACTION_IMPORT => ['import'],
                    ]),
                ],
            ],
            [
                'key' => 'leave-management',
                'label' => 'Quản lý phép',
                'applications' => [
                    self::app('leave-management.access', 'Truy cập quản lý phép', [
                        self::ACTION_VIEW => ['index', 'show'],
                    ]),
                    self::app('leave-management.personnel', 'Quân nhân / nhân sự phép', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                        self::ACTION_IMPORT => ['import'],
                    ]),
                    self::app('leave-management.requests', 'Đề xuất nghỉ phép', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                    ]),
                    self::app('leave-management.approvals', 'Duyệt nghỉ phép', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_APPROVE => ['approve'],
                    ]),
                    self::app('leave-management.catalogs', 'Danh mục quản lý phép', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                        self::ACTION_IMPORT => ['import'],
                    ]),
                    self::app('leave-management.regulations', 'Quy định phép', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                    ]),
                    self::app('leave-management.batches', 'Đợt nghỉ', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                    ]),
                    self::app('leave-management.records', 'Hồ sơ phép', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_EDIT => ['edit'],
                    ]),
                    self::app('leave-management.reports', 'Báo cáo phép', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_EXPORT => ['export'],
                    ]),
                    self::app('leave-management.audit', 'Nhật ký xử lý phép', [
                        self::ACTION_VIEW => ['index', 'show'],
                    ]),
                    self::app('leave-management.alerts', 'Thông báo phép', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_EDIT => ['edit'],
                    ]),
                    self::app('leave-management.mail', 'Cấu hình email phép', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_EDIT => ['edit'],
                    ]),
                    self::app('leave-management', 'Quản lý phép (tương thích quyền cũ)', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_CREATE => ['create'],
                        self::ACTION_EDIT => ['edit'],
                        self::ACTION_DELETE => ['delete'],
                        self::ACTION_APPROVE => ['approve'],
                        self::ACTION_EXPORT => ['export'],
                        self::ACTION_IMPORT => ['import'],
                    ]),
                ],
            ],
            [
                'key' => 'system',
                'label' => 'Hệ thống và dữ liệu dùng chung',
                'applications' => [
                    self::app('dashboards', 'Dashboard tổng quan', [self::ACTION_VIEW => ['index']],
                        'Số liệu tổng hợp từ Lịch đào tạo, LMS và Quản lý điểm.'),
                    self::app('units', 'Đơn vị (Khoa / Phòng / Ban)', self::CRUD_CORE),
                    self::app('instructors', 'Hồ sơ giảng viên', self::CRUD_CORE),
                    self::app('buildings', 'Giảng đường', self::CRUD_CORE),
                    self::app('classrooms', 'Phòng học', self::CRUD_CORE),
                    self::app('student-schedule', 'Lịch học của học viên', [self::ACTION_VIEW => ['index', 'show']],
                        'Màn hình học viên tự xem lịch của mình.'),
                    self::app('instructor-schedule', 'Lịch giảng của giảng viên', [self::ACTION_VIEW => ['index', 'show']],
                        'Màn hình giảng viên tự xem lịch dạy của mình.'),
                    self::app('export-templates', 'Mẫu xuất dữ liệu', self::CRUD_CORE,
                        'Mẫu Word/Excel dùng chung cho Dashboard, LMS và Quản lý điểm.'),
                    self::app('trash', 'Thùng rác', [
                        self::ACTION_VIEW => ['index', 'show'],
                        self::ACTION_EDIT => ['restore'],
                        self::ACTION_DELETE => ['delete'],
                    ], 'Sửa = khôi phục bản ghi đã xóa mềm. Xóa = xóa vĩnh viễn.'),
                ],
            ],
            [
                'key' => 'users',
                'label' => 'Người dùng',
                'applications' => [
                    self::app('users', 'Tài khoản người dùng', self::CRUD_CORE,
                        'Gồm cả nhập khẩu danh sách và gán đơn vị công tác.'),
                    self::app('roles', 'Vai trò', self::CRUD_CORE,
                        'Quyền nhạy cảm: ai sửa được vai trò là sửa được quyền của mọi người.'),
                    self::app('permissions', 'Quyền hệ thống', self::CRUD_CORE,
                        'Danh mục permission thô — chỉ quản trị hệ thống mới cần.'),
                ],
            ],
        ];

        // Tách riêng tổ chức thi và quản lý vật tư trên ma trận phân quyền.
        $subsystems = collect($subsystems)->flatMap(function (array $subsystem): array {
            if ($subsystem['key'] !== 'inventory-exam') {
                return [$subsystem];
            }

            $applications = collect($subsystem['applications']);

            return [
                [
                    'key' => 'exam-organization',
                    'label' => 'Tổ chức thi',
                    'applications' => $applications->filter(fn (array $application): bool => ! str_starts_with($application['key'], 'inventory'))->values()->all(),
                ],
                [
                    'key' => 'inventory-management',
                    'label' => 'Quản lý vật tư',
                    'applications' => $applications->filter(fn (array $application): bool => str_starts_with($application['key'], 'inventory'))->values()->all(),
                ],
            ];
        })->values()->all();

        return $subsystems;
    }

    /**
     * 15 ứng dụng Giờ chuẩn GV theo đúng "Bảng phân quyền vai trò".
     *
     * @return list<array{key: string, label: string, permission: string, actions: array<string, list<string>>, note?: string}>
     */
    private static function standardHoursApplications(): array
    {
        return [
            self::app('standard-hours', 'Truy cập phân hệ Giờ chuẩn GV', [
                self::ACTION_VIEW => ['index', 'show', 'view'],
            ], 'Bắt buộc tick Xem thì tài khoản mới vào được phân hệ.'),
            self::app('standard-hours.object-types', 'Đối tượng (định mức GC/NCKH)', self::CRUD_STANDARD_HOURS),
            self::app('standard-hours.positions', 'Chức danh (tỷ lệ)', self::CRUD_STANDARD_HOURS),
            self::app('standard-hours.departments', 'Bộ môn (Khoa)', self::CRUD_STANDARD_HOURS),
            self::app('standard-hours.department-overtime', 'Vượt định mức', self::CRUD_STANDARD_HOURS),
            self::app('standard-hours.norm-reductions', 'Giảm trừ định mức', self::CRUD_STANDARD_HOURS),
            self::app('standard-hours.conversion-categories', 'Hoạt động chuyên môn (danh mục)', self::CRUD_STANDARD_HOURS),
            self::app('standard-hours.research-categories', 'Danh mục NCKH', self::CRUD_STANDARD_HOURS),
            self::app('standard-hours.calculations', 'Tính giờ chuẩn', [
                self::ACTION_VIEW => ['view'],
                self::ACTION_CREATE => ['create', 'run'],
                self::ACTION_EDIT => ['edit'],
                self::ACTION_DELETE => ['delete'],
                self::ACTION_APPROVE => ['approve'],
            ], 'Thêm = chạy tính giờ chuẩn cho kỳ.'),
            self::app('standard-hours.reports', 'Báo cáo thống kê', [
                self::ACTION_VIEW => ['view'],
                self::ACTION_EXPORT => ['export'],
            ], 'Báo cáo chỉ đọc — không có Thêm/Sửa/Xóa.'),
            self::app('standard-hours.settings.period-mode', 'Kỳ tính năm học', [
                self::ACTION_VIEW => ['view'],
                self::ACTION_EDIT => ['edit'],
            ]),
            self::app('standard-hours.settings.research-rules', 'Luật quy đổi', [
                self::ACTION_VIEW => ['view'],
                self::ACTION_EDIT => ['edit'],
            ]),
            self::app('standard-hours.conversion-records', 'Kê khai hoạt động chuyên môn', self::CRUD_STANDARD_HOURS + [
                self::ACTION_APPROVE => ['approve'],
            ]),
            self::app('standard-hours.research-records', 'Kê khai NCKH', self::CRUD_STANDARD_HOURS + [
                self::ACTION_APPROVE => ['approve'],
            ]),
            self::app('standard-hours.external-activities', 'Hoạt động ngoài HĐCM', self::CRUD_STANDARD_HOURS + [
                self::ACTION_APPROVE => ['approve'],
            ]),
            self::app('standard-hours.hour-exchanges', 'Quyết định bù giờ', self::CRUD_STANDARD_HOURS),
            self::app('standard-hours.declarations', 'Hồ sơ kê khai giờ chuẩn', self::CRUD_STANDARD_HOURS + [
                self::ACTION_APPROVE => ['approve'],
            ]),
        ];
    }

    /**
     * Các ứng dụng của phân hệ LMS, bám theo màn hình thật trong
     * `modules/Lms` (shell quản trị, cổng giảng viên và cổng học viên).
     *
     * @return list<array{key: string, label: string, permission: string, actions: array<string, list<string>>, note?: string}>
     */
    private static function lmsApplications(): array
    {
        return [
            self::app('lms', 'Truy cập LMS / Danh sách khóa học', self::CRUD_CORE + [
                self::ACTION_APPROVE => ['manage'],
            ], 'Bắt buộc tick Xem thì tài khoản mới vào được LMS. Duyệt = quản trị mọi khóa, bỏ qua giới hạn "khóa mình dạy".'),
            self::app('lms.lessons', 'Bài giảng trong khóa', self::CRUD_LMS),
            self::app('lms.materials', 'Tài liệu học tập', self::CRUD_LMS),
            self::app('lms.assignments', 'Bài tập & chấm bài', self::CRUD_LMS + [
                self::ACTION_APPROVE => ['grade'],
            ], 'Duyệt = chấm điểm bài nộp của học viên.'),
            self::app('lms.exams', 'Thi online', self::CRUD_LMS + [
                self::ACTION_APPROVE => ['grade'],
            ], 'Duyệt = chấm và mở khóa lượt thi.'),
            self::app('lms.question-banks', 'Ngân hàng câu hỏi', self::CRUD_LMS),
            self::app('lms.attendance', 'Điểm danh lớp học số', self::CRUD_LMS,
                'Nối trực tiếp với tiết học trong Lịch đào tạo (schedule_details).'),
            self::app('lms.gradebook', 'Sổ điểm LMS', self::CRUD_LMS + [
                self::ACTION_APPROVE => ['transfer'],
                self::ACTION_EXPORT => ['export'],
            ], 'Duyệt = chuyển điểm tổng hợp sang phân hệ Quản lý điểm.'),
            self::app('lms.certificates', 'Chứng chỉ hoàn thành', self::CRUD_LMS + [
                self::ACTION_APPROVE => ['issue'],
            ], 'Duyệt = cấp chứng chỉ cho học viên đủ điều kiện.'),
            self::app('lms.surveys', 'Khảo sát khóa học', self::CRUD_LMS + [
                self::ACTION_APPROVE => ['publish'],
            ], 'Duyệt = phát hành khảo sát cho học viên.'),
            self::app('lms.alerts', 'Cảnh báo học tập', self::CRUD_LMS + [
                self::ACTION_APPROVE => ['resolve'],
            ], 'Duyệt = xử lý và đóng cảnh báo.'),
            self::app('lms.forum', 'Diễn đàn khóa học', self::CRUD_LMS),
            self::app('lms.chat', 'Tin nhắn lớp', self::CRUD_LMS),
            self::app('lms.progress', 'Tiến độ học tập', [
                self::ACTION_VIEW => ['view'],
                self::ACTION_EXPORT => ['export'],
            ], 'Chỉ đọc — tiến độ do hệ thống ghi nhận tự động.'),
            self::app('lms.members', 'Thành viên khóa học', self::CRUD_LMS + [
                self::ACTION_APPROVE => ['sync'],
            ], 'Duyệt = đồng bộ danh sách học viên từ lớp gốc.'),
            self::app('lms.scorm', 'Học liệu SCORM', self::CRUD_LMS),
            self::app('campus-network', 'Wi-Fi trường (điểm danh QR)', self::CRUD_CORE,
                'Cấu hình dải IP / GPS dùng để xác minh điểm danh tại trường.'),
        ];
    }

    /**
     * Các ứng dụng của phân hệ Quản lý điểm, bám theo `modules/Grades`.
     *
     * @return list<array{key: string, label: string, permission: string, actions: array<string, list<string>>, note?: string}>
     */
    private static function gradeApplications(): array
    {
        return [
            self::app('grades', 'Truy cập Quản lý điểm', self::CRUD_CORE + [
                self::ACTION_APPROVE => ['manage'],
            ], 'Bắt buộc tick Xem thì tài khoản mới vào được phân hệ. Duyệt = phạm vi toàn trường (Phòng Đào tạo).'),
            self::app('grades.books', 'Bảng điểm môn học', self::CRUD_GRADES,
                'Mỗi bảng điểm gắn với một lớp + một môn; khóa LMS chuyển điểm sang đây.'),
            self::app('grades.columns', 'Cột điểm & trọng số', self::CRUD_GRADES,
                'Điểm thành phần, hệ số và cách tính điểm tổng.'),
            self::app('grades.entry', 'Nhập điểm', self::CRUD_GRADES,
                'Nhập trực tiếp hoặc nhập theo lô cho từng cột điểm.'),
            self::app('grades.approval', 'Khóa & phê duyệt bảng điểm', [
                self::ACTION_VIEW => ['view'],
                self::ACTION_APPROVE => ['approve'],
                self::ACTION_EDIT => ['lock'],
            ], 'Sửa = khóa/mở khóa bảng điểm. Duyệt = phê duyệt chốt điểm.'),
            self::app('grades.requests', 'Yêu cầu sửa điểm sau khóa', [
                self::ACTION_VIEW => ['view'],
                self::ACTION_CREATE => ['create'],
                self::ACTION_APPROVE => ['review'],
            ], 'Giảng viên đề nghị, cấp quản lý xét duyệt.'),
            self::app('grades.conduct', 'Hạnh kiểm / rèn luyện', self::CRUD_GRADES),
            self::app('grades.graduation', 'Xét tốt nghiệp', self::CRUD_GRADES + [
                self::ACTION_APPROVE => ['approve'],
            ], 'Duyệt = chốt kết quả xét tốt nghiệp của đợt.'),
            self::app('grades.extracts', 'Trích xuất bảng điểm', [
                self::ACTION_VIEW => ['view'],
                self::ACTION_EXPORT => ['export'],
            ], 'Bảng điểm cá nhân, bảng điểm lớp, hồ sơ học viên.'),
        ];
    }

    /**
     * @param  array<string, list<string>>  $actions
     * @return array{key: string, label: string, permission: string, actions: array<string, list<string>>, note?: string}
     */
    private static function app(string $permission, string $label, array $actions, ?string $note = null): array
    {
        $entry = [
            'key' => $permission,
            'label' => $label,
            'permission' => $permission,
            'actions' => $actions,
        ];

        if ($note !== null) {
            $entry['note'] = $note;
        }

        return $entry;
    }

    /**
     * Danh sách ứng dụng phẳng, key = permission prefix.
     *
     * @return array<string, array{key: string, label: string, permission: string, actions: array<string, list<string>>, note?: string, subsystem: string, subsystemLabel: string}>
     */
    public static function applications(): array
    {
        $flat = [];

        foreach (self::subsystems() as $subsystem) {
            foreach ($subsystem['applications'] as $application) {
                $flat[$application['key']] = $application + [
                    'subsystem' => $subsystem['key'],
                    'subsystemLabel' => $subsystem['label'],
                ];
            }
        }

        return $flat;
    }

    public static function label(string $applicationKey): string
    {
        return self::applications()[$applicationKey]['label'] ?? $applicationKey;
    }

    /**
     * Permission thật của một ô trong ma trận.
     *
     * @return list<string>
     */
    public static function permissionNamesFor(string $applicationKey, string $action): array
    {
        $application = self::applications()[$applicationKey] ?? null;
        if ($application === null) {
            return [];
        }

        return array_map(
            fn (string $suffix) => $application['permission'].'.'.$suffix,
            $application['actions'][$action] ?? []
        );
    }

    /**
     * Toàn bộ permission mà registry khai báo.
     *
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        $names = [];

        foreach (self::applications() as $application) {
            foreach ($application['actions'] as $suffixes) {
                foreach ($suffixes as $suffix) {
                    $names[] = $application['permission'].'.'.$suffix;
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Toàn bộ permission của một phân hệ.
     *
     * @return list<string>
     */
    public static function subsystemPermissionNames(string $subsystemKey): array
    {
        $names = [];

        foreach (self::applications() as $application) {
            if ($application['subsystem'] !== $subsystemKey) {
                continue;
            }

            foreach ($application['actions'] as $suffixes) {
                foreach ($suffixes as $suffix) {
                    $names[] = $application['permission'].'.'.$suffix;
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Permission do registry sinh ra cho một prefix, dạng prefix => [action…]
     * — dùng khi seed permission trong SyncPermissionsAndRoles.
     *
     * @return array<string, list<string>>
     */
    public static function permissionDefinitions(): array
    {
        $definitions = [];

        foreach (self::applications() as $application) {
            $suffixes = [];
            foreach ($application['actions'] as $actionSuffixes) {
                foreach ($actionSuffixes as $suffix) {
                    $suffixes[$suffix] = true;
                }
            }

            $definitions[$application['permission']] = array_keys($suffixes);
        }

        return $definitions;
    }

    /**
     * Quyền gộp cũ → quyền chi tiết tương đương.
     *
     * Dùng để migrate role đang giữ `.manage` (và các quyền tổng của phân hệ
     * Giờ chuẩn) sang bộ Thêm/Sửa/Xóa chi tiết mà không ai bị mất quyền.
     *
     * @return array<string, list<string>>
     */
    public static function legacyPermissionMap(): array
    {
        $map = [];

        foreach (self::applications() as $application) {
            if (! in_array($application['permission'], self::LEGACY_MANAGE_PREFIXES, true)) {
                continue;
            }

            $granular = [];
            foreach ([self::ACTION_CREATE, self::ACTION_EDIT, self::ACTION_DELETE] as $action) {
                foreach (self::permissionNamesFor($application['key'], $action) as $name) {
                    $granular[] = $name;
                }
            }

            if ($granular !== []) {
                $map[$application['permission'].'.manage'] = $granular;
            }
        }

        // Chạy tính giờ chuẩn trước đây là `.run`, nay nằm dưới cột "Thêm".
        $map['standard-hours.calculations.run'] = ['standard-hours.calculations.create'];

        // Hai trang cài đặt tách ra từ `standard-hours.settings`.
        $map['standard-hours.settings.view'] = [
            'standard-hours.settings.period-mode.view',
            'standard-hours.settings.research-rules.view',
        ];
        $map['standard-hours.settings.manage'] = [
            'standard-hours.settings.period-mode.view',
            'standard-hours.settings.period-mode.edit',
            'standard-hours.settings.research-rules.view',
            'standard-hours.settings.research-rules.edit',
        ];

        return $map;
    }
}
