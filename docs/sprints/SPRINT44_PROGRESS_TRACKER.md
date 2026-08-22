# 📊 SPRINT 44 — PROGRESS TRACKER

**Ngày bắt đầu:** 2026-08-05
**Hoàn thành:** 2026-08-07
**Trạng thái chung:** 🟢 Toàn bộ Wave A→D đã triển khai, test xanh, đã commit

---

## 📈 TỔNG QUAN

| Đợt | Ưu tiên | Trạng thái | Mục tiêu |
|---|---|---|---|
| **A** — Chặn quyền, lỗi chặn người dùng | 🔴 P0 | 🟢 Hoàn thành | Không lộ chức năng ngoài quyền |
| **B** — Lọc liên động, phân công khoa | 🟠 P1 | 🟢 Hoàn thành | Lọc server + cấu trúc dữ liệu mới |
| **C** — LMS theo lớp, điểm danh | 🟠 P1 | 🟢 Hoàn thành | Ghi danh cả lớp + điểm danh mặc định |
| **D** — Rà UX chat/SCORM | 🟡 P2 | 🟢 Hoàn thành | Chat + SCORM fullscreen |

**Test:** 43/43 pass. Đã chia 6 commit trên nhánh `feat/lms-roadmap-integration`.

---

## 🚀 WAVE A — Chặn quyền (P0)

| # | Việc | Kết quả |
|---|---|---|
| A1 | Sidebar ẩn theo quyền | ✅ Bỏ chuỗi `OR isRole()`, dùng thuần `->can()` cho 5 mục + 2 bonus (Mẫu xuất Dashboard, Quản lý điểm) |
| A2 | Ẩn nút Thêm/Sửa/Xóa | ✅ 8 trang danh mục dùng `array_filter() + auth()->can()` |
| A3 | Khoá "Cập nhật & Ngày tiếp theo" | ✅ Kiểm tra `can('schedule-details.create')` trước redirect, fallback cảnh báo cho Khoa |
| A4 | Lỗi cuộn khi bấm logo | ✅ Bỏ `overflow-hidden` sai chỗ trên `<html>` |
| A5 | Khoá sửa lịch >48h | ✅ Config `edit_grace_hours`, Super Admin có ngoại lệ + ghi log |

**Phát hiện phụ:** Bug cache `ApprovalAgency::unitCode()` — `loadMissing('unit:id,code')` cắt cột khiến `loadMissing('unit')` đầy đủ gọi sau trong cùng request bị Eloquent bỏ qua. Đã sửa dùng `relationLoaded()` + query trực tiếp.

**Test:** `ScheduleDetailEditLockTest` (8/8), `TrainingScheduleScopeEnforcementTest` (4/4, hồi quy)

---

## 🔀 WAVE B — Lọc liên động + Kiến trúc dữ liệu (P1)

| # | Việc | Kết quả |
|---|---|---|
| B1 | Tạo/sửa Môn: Hệ → Ngành | ✅ Form Sửa thiếu hẳn ô "Hệ đào tạo" — đã thêm, dùng chung cơ chế cascade với form Tạo |
| B2 | Sửa Lịch đào tạo: Ngành → Lớp | ✅ JS cũ ghi thẳng `innerHTML` không tương thích TomSelect — thay bằng `setTomSelectOptions()` |
| B3 | `faculty_code` + `abbreviation` | ✅ Xem chi tiết kiến trúc bên dưới |

### B3 — Chi tiết kiến trúc phân công Khoa

Theo phản hồi trực tiếp: mã môn học không nên là nguồn suy ra Khoa phụ trách (môn NVQY không có hậu tố, đổi Khoa sau này phải đổi mã môn, mã môn có thể theo quy ước Bộ...).

- Thêm cột `subjects.faculty_code` — phân công tường minh qua form Môn học ("Khoa phụ trách"), lưu quan hệ bằng giá trị mã Khoa
- Migration backfill: mã môn cũ đúng mẫu hậu tố K1-K8 được nạp sẵn tự động, không mất công đẩy lại dữ liệu
- `SubjectCodePrefix::applyFacultyCodeScope()` + `TrainingDept::subjectBelongsToFaculty()` ưu tiên cột `faculty_code`, fallback hậu tố mã môn cho dữ liệu chưa gán
- **Bug bắt được khi viết test:** `Subject::getFacultyCodeAttribute()` (accessor có sẵn) tính từ hậu tố mã môn, che mất cột DB mới — đã sửa accessor ưu tiên cột thật
- Thêm `units.abbreviation` (tên viết tắt dùng trong báo cáo/thống kê)

**Việc phát sinh sau đó (yêu cầu riêng, không nằm trong kế hoạch gốc):** phát hiện `units.faculty_code` ("Mã phạm vi khoa") là field *tách biệt* khỏi `units.code`, bị giới hạn cứng `Rule::in(K1..K8)` — Khoa thứ 9 không tạo được. Đã bỏ field riêng này khỏi form; `faculty_code` giờ tự động = chính Mã đơn vị khi Chức năng = Khoa chuyên môn. Bỏ giới hạn cứng K1-K8 ở mọi điểm chặn chức năng thật (`TrainingScheduleAccess`, `SubjectCodePrefix`, `GradeAccess` wizard chọn Khoa).

**Test:** `SubjectFormCascadeTest` (2/2), `SubjectFacultyAssignmentTest` (3/3), `TrainingScheduleClassCascadeTest` (2/2), `FacultyCodeNotHardcodedTest` (6/6)

---

## 🎓 WAVE C — LMS theo lớp + Điểm danh (P1)

| # | Việc | Kết quả |
|---|---|---|
| C1 | Wizard hiện số học viên | ✅ Endpoint `classStudentCount` — "Sẽ ghi danh N học viên" khi chọn lớp |
| C2 | Ghi danh cả lớp | ✅ Xác nhận `syncRoster()` đã đúng sẵn (ghi danh theo `class_id`), viết test khoá lại (60 học viên) |
| C3 | Kéo toàn bộ GV | ✅ Wizard (`createWithMembers`) trước đây chỉ gọi `registerManualInstructor()` (1 GV) — thêm gọi `syncInstructorsFromTeachingAssignment()` |
| C4 | Điểm danh mặc định "có mặt" | ✅ Đổi default select từ `absent` sang `present` |
| C5 | Ngưỡng vắng → cảnh báo | ✅ `subjects.absence_limit_percent` theo môn, vắng có phép không tính, vượt ngưỡng chỉ cảnh báo qua `lms_learning_alerts` có sẵn |

**Phát hiện phụ (đã báo, chưa sửa — ngoài phạm vi):**
- `TeachingAssignment` model thiếu `$table` → sai tên bảng nếu dùng Eloquent để ghi (chưa từng lộ vì code hiện tại chỉ dùng `DB::table()`)
- `Teach\AttendanceController::markAllPresent()` có backend nhưng chưa có nút UI

**Test:** `LmsAttendanceDefaultPresentTest`, `LmsCourseWizardPullsAllTeachingAssignedInstructorsTest`, `LmsCourseWizardStudentCountTest`, `LmsCourseEnrollsEntireClassTest`, `LmsAbsenceThresholdAlertTest` (4 case)

---

## 🎨 WAVE D — Rà UX (P2)

| # | Việc | Kết quả |
|---|---|---|
| D1 | Chat LMS | ✅ `poll()` trước đây luôn ép cuộn xuống cuối — sửa chỉ cuộn khi người đọc đang gần cuối |
| D2 | SCORM fullscreen | ✅ Xác nhận đã có sẵn (đánh giá gốc trong sprint doc lỗi thời) — viết test khoá lại |

**Chưa làm (ngoài DoD chính thức, ghi nhận để làm sau):** Chat "tải thêm" (pagination tin cũ)

**Test:** `LmsChatScrollBehaviorTest`, `LmsScormFullscreenTest`

---

## 🔧 Việc phát sinh ngoài kế hoạch — sửa icon Bootstrap Icons vỡ trên `/roles`, `/trash`

Báo lỗi thực tế: nút chỉ có icon (Xem/Xóa) hiện ô vuông trắng trống. Mất nhiều vòng chẩn đoán mới ra **3 nguyên nhân cộng dồn**:

1. CDN jsdelivr không ổn định trong môi trường mạng → tự lưu trữ bằng npm package
2. Vite/Lightning CSS (bundler Tailwind v4) làm hỏng escape Unicode `content:"\fXXX"` khi minify → chuyển sang copy file tĩnh nguyên bản từ `node_modules` vào `public/vendor/bootstrap-icons/` (script `scripts/copy-bootstrap-icons.mjs`, chạy sau `npm install` + trong `npm run build`), nạp bằng `<link>` không qua bundler
3. CSS auto-detect "icon-only button" dùng `:has(> i.bi:only-child)` — `:only-child` không đếm text node, nên nút có icon + chữ trần (không bọc `<span>`) bị nhận nhầm là icon-only, bị ép co lại che mất nền màu + chữ. Sửa 6 chỗ gắn class `.action-btn` (escape hatch có sẵn)

---

## 📝 CHỐT NGÀY

| Ngày | Sự kiện |
|---|---|
| 05-08 | Phản hồi nghiệm thu (11 mục), khởi động Sprint 44 |
| 06-08 | Phân tích + triển khai Wave A, B, C, D |
| 07-08 | Phản hồi trực tiếp về kiến trúc mã Khoa → sửa bỏ hardcode K1-K8; phát hiện + sửa 3 lớp bug icon; commit 6 phần theo từng mảng việc |

**Git:** nhánh `feat/lms-roadmap-integration`, 6 commit (Wave A, Wave B, Wave C, Wave D, bỏ hardcode mã Khoa, sửa icon)
