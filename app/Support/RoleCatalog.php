<?php

namespace App\Support;

/**
 * Nhóm vai trò của nhà trường và ma trận quyền mặc định của từng nhóm.
 *
 * Vai trò ở đây là "chức trách" (Quản lý khoa, Quản lý Phòng Đào tạo, Ban KT,
 * Ban KHQS, Giảng viên…), KHÔNG phải là phân hệ phần mềm. Một vai trò có thể
 * dùng nhiều phân hệ; quyền cụ thể do ma trận quyết định chứ không do tên vai
 * trò suy ra.
 *
 * Ma trận mặc định chỉ là điểm khởi đầu khi seed — quản trị viên chỉnh trực tiếp
 * trên màn hình Vai trò, và chỉnh tay sẽ không bị seed ghi đè.
 */
final class RoleCatalog
{
    public const FACULTY_MANAGER = 'faculty-manager';

    public const RESEARCH_AGENCY_MANAGER = 'research-agency-manager';

    public const EXAM_MANAGER = 'exam-manager';

    public const INSTRUCTOR = 'instructor';

    public const STUDENT = 'student';

    private const VIEW = ApplicationRegistry::ACTION_VIEW;

    private const CREATE = ApplicationRegistry::ACTION_CREATE;

    private const EDIT = ApplicationRegistry::ACTION_EDIT;

    private const DELETE = ApplicationRegistry::ACTION_DELETE;

    private const APPROVE = ApplicationRegistry::ACTION_APPROVE;

    private const EXPORT = ApplicationRegistry::ACTION_EXPORT;

    private const SUBMIT = ApplicationRegistry::ACTION_SUBMIT;

    private const IMPORT = ApplicationRegistry::ACTION_IMPORT;

    private const BANK = ApplicationRegistry::ACTION_BANK;

    private const DRAW = ApplicationRegistry::ACTION_DRAW;

    private const FULL = [self::VIEW, self::CREATE, self::EDIT, self::DELETE];

    /** Toàn trường — quản lý nghiệp vụ mọi phân hệ, không quản trị vai trò/quyền lõi. */
    public const SCHOOL_MANAGER = ManagementRole::SYSTEM_MANAGER;

    /** Quản lý đào tạo — Phòng Đào tạo. */
    public const TRAINING_MANAGER = ManagementRole::TRAINING_OFFICE_MANAGER;

    /**
     * Tám vai trò chuẩn của nhà trường. Vai trò phát sinh về sau do quản trị viên
     * tự tạo trên màn hình Vai trò, không cần khai báo ở đây.
     *
     * @return list<array{name: string, label: string, description: string, scope: string, abilities: array<string, list<string>>}>
     */
    public static function groups(): array
    {
        return [
            [
                'name' => ManagementRole::SUPER_ADMIN,
                'label' => 'Super Admin',
                'description' => 'Quản trị hệ thống. Luôn giữ toàn bộ quyền, không chỉnh ma trận và không xóa được.',
                'scope' => 'Toàn hệ thống',
                'abilities' => [],
            ],
            [
                'name' => self::SCHOOL_MANAGER,
                'label' => 'Quản lý toàn trường',
                'description' => 'Điều hành nghiệp vụ mọi phân hệ trong phạm vi toàn trường. Không quản trị tài khoản, vai trò và quyền hệ thống.',
                'scope' => 'Toàn trường',
                'abilities' => self::schoolManagerAbilities(),
            ],
            [
                'name' => self::TRAINING_MANAGER,
                'label' => 'Quản lý đào tạo',
                'description' => 'Phòng Đào tạo: dựng chương trình đào tạo và xếp khung lịch cho toàn trường.',
                'scope' => 'Toàn trường',
                'abilities' => self::trainingOfficeAbilities(),
            ],
            [
                'name' => self::FACULTY_MANAGER,
                'label' => 'Quản lý khoa',
                'description' => 'Chủ nhiệm khoa: gán bài học và giảng viên trên khung lịch của Phòng Đào tạo, theo dõi giờ chuẩn, LMS và điểm của khoa mình.',
                'scope' => 'Lọc theo đơn vị gán cho tài khoản',
                'abilities' => self::facultyManagerAbilities(),
            ],
            [
                'name' => self::EXAM_MANAGER,
                'label' => 'Quản lý khảo thí',
                'description' => 'Ban Khảo thí: thẩm định kê khai hoạt động chuyên môn và quản lý điểm toàn trường.',
                'scope' => 'Toàn trường',
                'abilities' => self::examManagerAbilities(),
            ],
            [
                'name' => self::RESEARCH_AGENCY_MANAGER,
                'label' => 'Quản lý Khoa học quân sự',
                'description' => 'Ban Khoa học Quân sự: thẩm định kê khai nghiên cứu khoa học và quản trị danh mục NCKH.',
                'scope' => 'Toàn trường',
                'abilities' => self::researchAgencyAbilities(),
            ],
            [
                'name' => self::INSTRUCTOR,
                'label' => 'Giảng viên',
                'description' => 'Tự kê khai giờ quy đổi và NCKH; dạy trên LMS và nhập điểm cho lớp mình phụ trách.',
                'scope' => 'Lớp / khóa được phân công',
                'abilities' => self::instructorAbilities(),
            ],
            [
                'name' => self::STUDENT,
                'label' => 'Học viên',
                'description' => 'Xem lịch học, tham gia lớp học số và xem điểm của chính mình.',
                'scope' => 'Dữ liệu của chính học viên',
                'abilities' => self::studentAbilities(),
            ],
        ];
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::groups() as $group) {
            $labels[$group['name']] = $group['label'];
        }

        return $labels;
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_column(self::groups(), 'name');
    }

    /** @return array{name: string, label: string, description: string, abilities: array<string, list<string>>}|null */
    public static function find(string $name): ?array
    {
        foreach (self::groups() as $group) {
            if ($group['name'] === $name) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Tên permission mặc định của một nhóm vai trò.
     *
     * @return list<string>
     */
    public static function permissionNames(string $name): array
    {
        $group = self::find($name);
        if ($group === null) {
            return [];
        }

        $names = [];
        foreach ($group['abilities'] as $application => $actions) {
            foreach ($actions as $action) {
                foreach (ApplicationRegistry::permissionNamesFor($application, $action) as $permission) {
                    $names[] = $permission;
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Quản lý toàn trường — dùng được mọi ứng dụng nghiệp vụ, trừ phân hệ
     * Người dùng (tài khoản / vai trò / quyền) dành riêng cho Super Admin.
     *
     * @return array<string, list<string>>
     */
    private static function schoolManagerAbilities(): array
    {
        $abilities = [];

        foreach (ApplicationRegistry::applications() as $key => $application) {
            if ($application['subsystem'] === 'users') {
                continue;
            }

            $abilities[$key] = array_keys($application['actions']);
        }

        // Hai phân hệ nghiệp vụ được bổ sung sau ApplicationRegistry cũ.
        $abilities['inventory'] = [self::VIEW, self::CREATE, self::EDIT, self::DELETE, self::APPROVE, self::EXPORT, self::IMPORT];
        $abilities['leave-management'] = [self::VIEW, self::CREATE, self::EDIT, self::DELETE, self::APPROVE, self::EXPORT, self::IMPORT];

        return $abilities;
    }

    /**
     * Quản lý khoa — theo đúng "Bảng phân quyền vai trò" mục 1, cộng phần Lịch
     * đào tạo, LMS và Điểm mà khoa đảm nhiệm. Mọi màn hình đều bị lọc thêm theo
     * đơn vị gán cho tài khoản (TrainingDept / GradeAccess / LmsAccess).
     *
     * @return array<string, list<string>>
     */
    private static function facultyManagerAbilities(): array
    {
        return [
            // Giờ chuẩn GV — danh mục chỉ xem
            'standard-hours' => [self::VIEW],
            'standard-hours.object-types' => [self::VIEW],
            'standard-hours.positions' => [self::VIEW],
            'standard-hours.departments' => [self::VIEW],
            'standard-hours.conversion-categories' => [self::VIEW],
            'standard-hours.research-categories' => [self::VIEW],
            'standard-hours.settings.period-mode' => [self::VIEW],
            'standard-hours.settings.research-rules' => [self::VIEW],
            'standard-hours.hour-exchanges' => [self::VIEW],
            'standard-hours.declarations' => [self::VIEW],
            // Giờ chuẩn GV — nghiệp vụ của khoa, làm đầy đủ
            'standard-hours.department-overtime' => self::FULL,
            'standard-hours.norm-reductions' => self::FULL,
            'standard-hours.calculations' => self::FULL,
            'standard-hours.conversion-records' => self::FULL,
            'standard-hours.research-records' => self::FULL,
            'standard-hours.external-activities' => self::FULL,
            'standard-hours.reports' => [self::VIEW, self::EXPORT],
            // Lịch đào tạo — khoa gán bài học/giảng viên trên khung PĐT đã xếp
            'training-schedules' => [self::VIEW],
            'schedule-details' => [self::VIEW, self::EDIT],
            'teaching-assignments' => self::FULL,
            'subjects' => [self::VIEW],
            'subject-lessons' => [self::VIEW, self::CREATE, self::EDIT],
            'specializations' => [self::VIEW],
            'classes' => [self::VIEW],
            'exam-organization' => [self::VIEW, self::CREATE, self::EDIT],
            // Dữ liệu dùng chung
            'dashboards' => [self::VIEW],
            'units' => [self::VIEW],
            'instructors' => [self::VIEW],
            'classrooms' => [self::VIEW],
            'export-templates' => [self::VIEW],
            // LMS — khoa theo dõi lớp học số của môn thuộc khoa
            'lms' => [self::VIEW],
            'lms.lessons' => [self::VIEW],
            'lms.assignments' => [self::VIEW],
            'lms.exams' => [self::VIEW],
            'lms.attendance' => [self::VIEW],
            'lms.gradebook' => [self::VIEW, self::EXPORT],
            'lms.progress' => [self::VIEW, self::EXPORT],
            'lms.alerts' => [self::VIEW, self::APPROVE],
            'lms.members' => [self::VIEW],
            // Quản lý điểm — theo dõi và chốt điểm của khoa
            'grades' => [self::VIEW],
            'grades.books' => [self::VIEW],
            'grades.columns' => [self::VIEW],
            'grades.entry' => [self::VIEW],
            'grades.approval' => [self::VIEW, self::EDIT, self::APPROVE],
            'grades.requests' => [self::VIEW, self::APPROVE],
            'grades.conduct' => self::FULL,
            'grades.extracts' => [self::VIEW, self::EXPORT],
            // Tổ chức thi: khoa lập kế hoạch; các bước còn lại do Ban Khảo thí quản lý.
            'exam-organization' => [self::VIEW, self::CREATE],
            // Đề thi tự luận — tiếp nhận, duyệt và vận hành ngân hàng đề
            'essay-exams' => [self::VIEW],
            'essay-exams.approval' => [self::VIEW, self::APPROVE],
            'exam-organization' => [self::VIEW, self::EDIT],
        ];
    }

    /** @return array<string, list<string>> */
    private static function trainingOfficeAbilities(): array
    {
        return [
            'training-schedules' => self::FULL,
            'schedule-details' => self::FULL,
            'teaching-assignments' => [self::VIEW],
            'specializations' => self::FULL,
            'subjects' => self::FULL,
            'subject-lessons' => self::FULL,
            'classes' => [self::VIEW],
            'dashboards' => [self::VIEW],
            'units' => [self::VIEW],
            'instructors' => [self::VIEW],
            'classrooms' => [self::VIEW],
            'export-templates' => [self::VIEW],
            // LMS — mở khóa học theo lịch đã xếp
            'lms' => [self::VIEW, self::CREATE, self::EDIT],
            'lms.lessons' => [self::VIEW],
            'lms.members' => [self::VIEW, self::APPROVE],
            'lms.attendance' => [self::VIEW],
            'lms.progress' => [self::VIEW, self::EXPORT],
            // Quản lý điểm — phạm vi toàn trường
            'grades' => [self::VIEW, self::APPROVE],
            'grades.books' => self::FULL,
            'grades.columns' => self::FULL,
            'grades.approval' => [self::VIEW, self::EDIT, self::APPROVE],
            'grades.requests' => [self::VIEW, self::APPROVE],
            'grades.graduation' => array_merge(self::FULL, [self::APPROVE]),
            'grades.extracts' => [self::VIEW, self::EXPORT],
            // Phòng Đào tạo được cấp quyền quản lý hai phân hệ nghiệp vụ dùng chung.
            'inventory' => [self::VIEW, self::CREATE, self::EDIT, self::DELETE, self::APPROVE, self::EXPORT, self::IMPORT],
            'leave-management' => [self::VIEW, self::CREATE, self::EDIT, self::DELETE, self::APPROVE, self::EXPORT, self::IMPORT],
        ];
    }

    /**
     * Ban Khảo thí — thẩm định kê khai HĐCM và quản lý điểm.
     *
     * @return array<string, list<string>>
     */
    private static function examManagerAbilities(): array
    {
        return [
            // Giờ chuẩn GV — duyệt hoạt động chuyên môn
            'standard-hours' => [self::VIEW],
            'standard-hours.conversion-categories' => [self::VIEW, self::CREATE, self::EDIT],
            'standard-hours.conversion-records' => [self::VIEW, self::APPROVE],
            'standard-hours.external-activities' => [self::VIEW, self::APPROVE],
            'standard-hours.declarations' => [self::VIEW, self::APPROVE],
            'standard-hours.calculations' => [self::VIEW, self::APPROVE],
            'standard-hours.reports' => [self::VIEW, self::EXPORT],
            // Quản lý điểm — toàn trường
            'grades' => [self::VIEW, self::APPROVE],
            'grades.books' => self::FULL,
            'grades.columns' => self::FULL,
            'grades.entry' => self::FULL,
            'grades.approval' => [self::VIEW, self::EDIT, self::APPROVE],
            'grades.requests' => [self::VIEW, self::APPROVE],
            'grades.conduct' => self::FULL,
            'grades.graduation' => array_merge(self::FULL, [self::APPROVE]),
            'grades.extracts' => [self::VIEW, self::EXPORT],
            // Dữ liệu dùng chung
            'training-schedules' => [self::VIEW],
            'schedule-details' => [self::VIEW],
            'dashboards' => [self::VIEW],
            'instructors' => [self::VIEW],
            'classes' => [self::VIEW],
            'subjects' => [self::VIEW],
            'export-templates' => [self::VIEW],
            // Đề thi tự luận — Ban Khảo thí quản lý toàn bộ quy trình
            'essay-exams' => [self::VIEW],
            'essay-exams.approval' => [self::VIEW, self::APPROVE],
            'essay-exams.bank' => [self::VIEW, self::APPROVE, self::EXPORT],
            'essay-exams.draw' => [self::VIEW, self::CREATE, self::EXPORT],
        ];
    }

    /**
     * Ban Khoa học Quân sự — thẩm định kê khai NCKH.
     *
     * @return array<string, list<string>>
     */
    private static function researchAgencyAbilities(): array
    {
        return [
            'standard-hours' => [self::VIEW],
            'standard-hours.research-categories' => self::FULL,
            'standard-hours.research-records' => [self::VIEW, self::APPROVE],
            'standard-hours.declarations' => [self::VIEW],
            'standard-hours.calculations' => [self::VIEW],
            'standard-hours.reports' => [self::VIEW, self::EXPORT],
            'standard-hours.settings.research-rules' => [self::VIEW, self::EDIT],
            'dashboards' => [self::VIEW],
            'instructors' => [self::VIEW],
            'units' => [self::VIEW],
            'export-templates' => [self::VIEW],
        ];
    }

    /**
     * Giảng viên — tự kê khai, dạy trên LMS, nhập điểm lớp mình.
     * Phạm vi thực tế bị LmsAccess / GradeAccess siết theo khóa và lớp phụ trách.
     *
     * @return array<string, list<string>>
     */
    private static function instructorAbilities(): array
    {
        return [
            'instructor-schedule' => [self::VIEW],
            'dashboards' => [self::VIEW],
            'export-templates' => [self::VIEW],
            // Giờ chuẩn GV — tự kê khai
            'standard-hours' => [self::VIEW],
            'standard-hours.declarations' => self::FULL,
            'standard-hours.conversion-records' => self::FULL,
            'standard-hours.research-records' => self::FULL,
            'standard-hours.external-activities' => self::FULL,
            // LMS — dạy khóa được phân công
            'lms' => [self::VIEW, self::EDIT],
            'lms.lessons' => self::FULL,
            'lms.materials' => self::FULL,
            'lms.assignments' => array_merge(self::FULL, [self::APPROVE]),
            'lms.exams' => array_merge(self::FULL, [self::APPROVE]),
            'lms.question-banks' => self::FULL,
            'lms.attendance' => self::FULL,
            'lms.gradebook' => [self::VIEW, self::EDIT, self::APPROVE, self::EXPORT],
            'lms.surveys' => array_merge(self::FULL, [self::APPROVE]),
            'lms.alerts' => [self::VIEW, self::APPROVE],
            'lms.forum' => self::FULL,
            'lms.chat' => [self::VIEW, self::CREATE, self::EDIT],
            'lms.progress' => [self::VIEW, self::EXPORT],
            'lms.members' => [self::VIEW],
            'lms.scorm' => [self::VIEW],
            'lms.certificates' => [self::VIEW],
            // Quản lý điểm — nhập điểm lớp phụ trách
            'grades' => [self::VIEW],
            'grades.books' => [self::VIEW, self::CREATE],
            'grades.columns' => [self::VIEW, self::CREATE, self::EDIT],
            'grades.entry' => [self::VIEW, self::CREATE, self::EDIT],
            'grades.requests' => [self::VIEW, self::CREATE],
            'grades.extracts' => [self::VIEW, self::EXPORT],
            // Giảng viên được tạo/import, xem đề của mình và tra cứu ngân hàng đề
            'essay-exams' => [self::VIEW],
            'essay-exams.authoring' => [self::VIEW, self::CREATE],
            'essay-exams.import' => [self::VIEW, self::CREATE],
            'essay-exams.submission' => [self::VIEW, self::CREATE],
            'essay-exams.bank' => [self::VIEW],
            'exam-organization' => [self::VIEW],
        ];
    }

    /** @return array<string, list<string>> */
    private static function studentAbilities(): array
    {
        return [
            'student-schedule' => [self::VIEW],
            'dashboards' => [self::VIEW],
            // LMS — học viên tham gia khóa của mình
            'lms' => [self::VIEW],
            'lms.lessons' => [self::VIEW],
            'lms.materials' => [self::VIEW],
            'lms.assignments' => [self::VIEW, self::CREATE],
            'lms.exams' => [self::VIEW, self::CREATE],
            'lms.attendance' => [self::VIEW, self::CREATE],
            'lms.forum' => [self::VIEW, self::CREATE],
            'lms.chat' => [self::VIEW, self::CREATE],
            'lms.progress' => [self::VIEW],
            'lms.surveys' => [self::VIEW, self::CREATE],
            'lms.certificates' => [self::VIEW],
            'lms.scorm' => [self::VIEW],
            'lms.gradebook' => [self::VIEW],
        ];
    }
}
