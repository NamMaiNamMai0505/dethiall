# LMS & Dashboard — Inventory: đã có / còn thiếu

**Ngày cập nhật:** 2026-07-19 (rev: Sprint 9 + campus P0–P2 + LHL export; smoke + journal)  
**Project:** `D:\CDHC2\lich-hoc-cdhc2`  
**File:** `modules/Lms/docs/LMS_GAPS.md`  
**Cách dùng:** Backlog + bản đồ thực tế code (không thay FEATURE_SPEC/TODO giờ chuẩn).  
**Tham chiếu:** [SPRINT_GV.md](./SPRINT_GV.md), SPRINT1–5, `PROMPT_START.md`, `CLAUDE.md`,  
`docs/CODE_JOURNAL.md`, `docs/TEMPLATE_DEPENDENCIES.md`, `docs/SMOKE_TEST_REPORT.md`.

---

## 0. Bối cảnh dự án (theo PROMPT_START + kiến trúc)

Hệ thống **Quản lý đào tạo CDHC2** = nhiều module Laravel custom dưới `modules/` + RBAC Spatie (`module.action`).

| Lớp | Vai trò | Shell UI | Entry chính |
|-----|---------|----------|-------------|
| **Admin / Manager** | PDOT, Khoa, super-admin | `layouts.admin` + sidebar | `/dashboard`, các CRUD module |
| **LMS quản trị** | Cùng admin shell | Hub `/lms` | Tạo khóa, gradebook admin, … |
| **Portal HV** | role `student` | `layouts.lms-learner` (teal) | `/lms/hoc` |
| **Portal GV** | role `instructor` | Cùng shell LMS | `/lms/gv` + phòng học `?mode=teach` |
| **Giờ chuẩn** | Module `StandardHours` | Admin (và link navbar GV) | `/standard-hours/*`, kê khai GV |

**Nguyên tắc (PROMPT_START):** ưu tiên tái sử dụng module/service đã có; không tạo trùng; business logic trong Service; permission `module.action`; không phá BUSINESS_RULES.

**TODO.md / FEATURE_SPEC.md** trong repo chủ yếu mô tả epic **Giờ chuẩn giảng viên** — không phải roadmap LMS đầy đủ. Backlog LMS theo file này + `SPRINT_*.md`.

---

## 1. Bản đồ module toàn hệ thống (đã có)

### 1.1 Core / đào tạo (Dashboard admin)

| Module | Chức năng chính | Trạng thái |
|--------|-----------------|------------|
| Authentication | Login, redirect theo role (HV→LMS, GV→`/lms/gv`) | ✅ |
| Dashboard | Tổng quan + AJAX thống kê lớp / GV | ✅ |
| Home | Landing `/#` | ✅ |
| Unit | Đơn vị / khoa | ✅ |
| Specialization + TrainingSystem | Ngành + hệ đào tạo | ✅ |
| Class | Lớp học | ✅ |
| Subject + SubjectLesson | Môn + khung bài CTĐT | ✅ |
| Instructor | Hồ sơ GV | ✅ |
| TeachingAssignment | Phân công giảng dạy | ✅ |
| TrainingSchedule + ScheduleDetail | Khung lịch + chi tiết tiết | ✅ |
| Building + Classroom | Giảng đường / phòng | ✅ |
| Student + User | HV / tài khoản / roles | ✅ |
| StudentSchedule | Lịch học (module riêng; HV ưu tiên lịch trong LMS) | ✅ |
| InstructorSchedule | Lịch dạy admin-style (GV ưu tiên lịch trong LMS) | ✅ |
| StandardHours | Kê khai / định mức / báo cáo giờ chuẩn | ✅ (module lớn) |
| Trash | Thùng rác soft-delete | ✅ |
| **Campus Network** | Wi‑Fi MAC AP + CIDR điểm danh LMS | ✅ Admin sidebar `/campus-network` |
| **Lms** | Toàn bộ LMS (mục 2–4) | ✅ core + portal |

### 1.2 Permission LMS (tóm tắt)

| Permission | Ai có (ý) |
|------------|-----------|
| `lms.index` / `lms.show` | student, instructor, manager, super-admin |
| `lms.edit` | instructor (scope khóa mình), manager, super-admin |
| `lms.create` / `lms.delete` / `lms.manage` | manager / super-admin (không gán instructor) |
| `campus-network.*` | manager + super-admin (**không** instructor) |
| `dashboards.index` | các role có dashboard |
| `instructor-schedule.*` | instructor (lịch; portal LMS cũng dùng) |
| `standard-hours.*` | quản trị + kê khai theo unit |

---

## 2. Module LMS — đã có (inventory theo code)

### 2.1 Hạ tầng kỹ thuật

| Hạng mục | Chi tiết | Status |
|----------|----------|--------|
| Migrations | sprint1–5 + training_systems + chat DM/GPS fields + campus_wifi | ✅ |
| Models | Course, Lesson, Material, Scorm, Member, Assignment/Submission, Exam/Attempt/Bank/Question, Attendance Session/Record, Progress, Alert, GradebookRow, Certificate/Template, Survey/Question/Response, Forum, Chat, CampusNetworkSetting | ✅ |
| Services | Course, Material, Exam, Gradebook, Progress, Certificate, Alert | ✅ |
| Support | `LmsAccess`, `LmsCampus` (GPS P2), `CampusNetwork` (IP/CIDR P0 + probe P1), `CheckinStats` | ✅ |
| Shell HV/GV | `lms-learner` + theme + Turbo soft nav + Tom Select + Flatpickr + sticky navbar | ✅ |
| Toast / confirm popup | `partials/notifications` — `Notify` toast + `uiConfirm` modal; session flash → popup (LMS + admin) | ✅ |
| Seeder demo | `LmsDemoSeeder` (HV/GV/admin demo) | ✅ |
| Public verify CC | `/lms/certificates/verify` | ✅ |

### 2.2 Admin LMS (`/lms/*`, shell admin)

| Chức năng | Route / controller (ý) | Status |
|-----------|------------------------|--------|
| Hub khóa | `lms.hub` | ✅ |
| CRUD khóa LMS | CourseController | ✅ |
| Wizard tạo khóa 1 bước | `lms.courses.create` → `createWithMembers` | ✅ Sprint 8 M1 |
| Export điểm multi-course | `lms.gradebook.export-multi` | ✅ Sprint 8 M2 |
| Sync members từ lớp/core | `syncMembers` + `LmsCourseService::syncMembersFromCore` | ✅ manual + **job** `lms:sync-members` (M4) |
| Dashboard widget LMS | tab Dashboard + `LmsStatisticsService` | ✅ Sprint 8 M5 |
| Members add/remove | MemberController | ✅ |
| Lessons CRUD | LessonController | ✅ |
| Materials + SCORM upload | MaterialController | ✅ |
| Forum + Chat (admin views) | Forum/ChatController | ✅ |
| Assignments + chấm (admin) | AssignmentController | ✅ |
| Exams NHCH + đề + attempts (admin) | ExamController | ✅ |
| Gradebook matrix + **override** + refresh | GradebookController | ✅ **admin** |
| Attendance sessions + mark + close | AttendanceController | ✅ **admin** |
| Progress list | ProgressController | ✅ |
| Alerts list + **evaluate** + **resolve** | AlertController | ✅ **admin** |
| Certificates template + **issueOne/issueEligible** + show | CertificateController | ✅ **admin** (+ HV request) |
| Surveys create + questions + **publish** + stats | SurveyController | ✅ **admin** (+ HV submit) |
| Wi‑Fi trường CRUD + Test IP + Stats | CampusNetworkController `/campus-network` | ✅ **admin only** (P0–P2) |

### 2.3 Portal học viên (`/lms/hoc/*`)

| Chức năng | Status | Ghi chú |
|-----------|--------|---------|
| Home khóa của tôi | ✅ | |
| Lịch học (calendar) | ✅ | Trong LMS, không bắt buộc StudentSchedule admin |
| Profile (mã HV, lớp, đơn vị, đổi pass) | ✅ | |
| Phòng học SPA-tabs | ✅ | overview, lessons, materials, assignments, exams, attendance calendar, progress, grades, certificates, surveys, forum, chat |
| Xem bài / học liệu / SCORM player | ✅ | PPTX: tải về / PDF khuyến nghị (SPRINT5) |
| Nộp bài tập (text + file) | ✅ | |
| Làm thi + proctor blur cơ bản + kết quả | ✅ | |
| Điểm danh self/QR/GPS + IP/probe/TTL/bypass | ✅ | P0–P2; manual chỉ GV |
| Tiến độ / điểm của tôi / cảnh báo (đọc) | ✅ | |
| Chứng chỉ: xem + request issue | ✅ | |
| Khảo sát: làm + submit | ✅ | |
| Forum topic/reply | ✅ | |
| Chat group + DM + poll | ✅ | Bị khóa nếu GV lock |

### 2.4 Portal giảng viên (`/lms/gv/*` + `?mode=teach`)

| Sprint | Chức năng | Status |
|--------|-----------|--------|
| **GV-1** | Home khóa dạy, navbar Khóa/Lịch/Dashboard giờ chuẩn/Cá nhân, `canTeachCourse`, mode teach | ✅ |
| **GV-2** | Tab Soạn bài: CRUD lesson, upload TL/SCORM, publish, sort | ✅ |
| **GV-3** | Tab Giao & chấm: BT CRUD, chấm, feedback, notify, **tải 1 + ZIP lớp** | ✅ |
| **GV-4** + S6 | Tab Thi online: NHCH CRUD câu (sửa/xóa/↑↓), tạo đề **chọn câu lẻ**, toggle, attempts, CSV | ✅ |
| **GV-5** | Tab Điểm danh: manual/self/QR/GPS, QR popup + **TTL/rotate**, mark, % chuyên cần, IP/mạng/GPS flags | ✅ |
| **GV-6** + S6 | Tab Lớp học: matrix + **override điểm** + refresh snapshot; alerts/KS/CC (xem) | ✅ (KS/CC/alert action → Sprint 7) |
| **GV-7** | Tab Tương tác: announce lớp; chat lock/delete; forum pin/lock | ✅ |
| **GV-8** | Lịch dạy LMS + deep-link khóa; profile mã GV/đơn vị; Tom Select/Flatpickr shell | ✅ (polish optional) |

**Redirect login GV:** `AuthenticationService` → `lms.teach.home` (lịch nằm LMS; Dashboard = giờ chuẩn).

---

## 3. Phân tách rõ: đã có nhưng **chỉ admin**, chưa trên portal GV

Những API/UI **đã implement** — không phải “chưa làm hệ thống”, mà **chưa wire vào mode teach**:

| # | Hạng mục | Đã có ở đâu | Thiếu trên portal GV |
|---|----------|-------------|----------------------|
| A1 | **Override điểm** + refresh gradebook | Admin + **teach tab Lớp học** (Sprint 6) | ✅ Đã wire teach |
| A2 | **Tạo / publish khảo sát** | `SurveyController@store/storeQuestion/publish` | GV chỉ xem TB rating (GV-6) |
| A3 | **Cấp chứng chỉ** (1 / eligible) + template | CertificateController admin | GV chỉ cột “đã cấp” |
| A4 | **Evaluate / resolve alerts** | AlertController + LmsAlertService | GV chỉ list cảnh báo mở |
| A5 | **Tạo khóa LMS** | CourseController create | Cố ý: instructor không `lms.create` |
| A6 | **Sync members** | CourseController + service | GV không bấm sync (admin) |
| A7 | Exam admin UI đầy đủ attempts page | exams/attempts admin + teach attempts | Teach đã có trang lượt + CSV |

→ Backlog ưu tiên: **mở rộng reuse** controller/service sang route teach + UI tab, **không viết lại**.

---

## 4. Còn thiếu / làm dở thật (gap)

### 4.1 Portal GV (chức năng chưa có hoặc partial)

| # | Hạng mục | Mức | Ghi chú |
|---|----------|-----|---------|
| G1 | Chọn **câu lẻ** khi tạo đề (multi-select NHCH) | ✅ | Sprint 6 — tick câu + optional bank |
| G2 | **Sửa / xóa / reorder** câu trong NHCH | ✅ | Sprint 6 — tab Thi online |
| G3 | Wire **override điểm** + refresh lên tab Lớp học | ✅ | Sprint 6 — `lms.teach.gradebook.*` |
| G4 | Wire **soạn/publish khảo sát** lên teach | ✅ | Sprint 7 — tab Lớp học |
| G5 | Wire **issue chứng chỉ** (đủ ĐK, không force) | ✅ | Sprint 7 — policy GV không force |
| G6 | Wire **evaluate/resolve alert** | ✅ | Sprint 7 — quét + ghi chú resolve |
| G7 | Filter bài nộp **chờ chấm only** | ✅ | Sprint 8 — `?pending_only=1` tab assign |
| G8 | Map `subject_lesson_id` bài LMS ↔ CTĐT | ✅ | Sprint 8 — UI teach author + ContentController |
| G9 | HV **nộp lại** sau feedback | ✅ | Sprint 8 — reset status submitted |
| G10 | Deep-link lịch khi 1 subject nhiều lớp mơ hồ | ✅ | Sprint 8 — `lms_alternatives` chọn lớp |
| G11 | Feature test scope GV + 403 page LMS đẹp | ✅ | Sprint 8 — `errors/lms-403` + `LmsSprint8OpsTest` |

### 4.2 Portal HV

| # | Hạng mục | Mức | Ghi chú |
|---|----------|-----|---------|
| H1 | Deep-link thông báo → đúng tab (BT/thi/điểm danh) | ✅ | Sprint 7 — `?tab=` + chọn tab khi announce |
| H2 | UI lịch sử điểm danh chi tiết (IP/network) cho HV | ✅ | Sprint 9 — calendar detail |
| H3 | Versioning lần nộp bài (UI) | ✅ | Sprint 9 — versions table + timeline |
| H4 | Offline / PWA | ✅ | Sprint 9 — light PWA shell (no offline submit queue) |
| H5 | Live video conference | ❌ | **Loại bỏ** — không làm |

### 4.3 Admin LMS / vận hành

| # | Hạng mục | Mức | Ghi chú |
|---|----------|-----|---------|
| M1 | Wizard tạo khóa (môn+lớp+GV+sync 1 bước) | ✅ | Sprint 8 — `courses/create` wizard 3 bước → createWithMembers |
| M2 | Báo cáo khoa / export điểm nhiều khóa | ✅ | Sprint 8 — CSV multi-course `/lms/gradebook/export-multi` |
| M3 | Designer template chứng chỉ (kéo-thả) | ✅ | Sprint 9 — layout_json + preview |
| M4 | Job **đồng bộ members định kỳ** | ✅ | Sprint 8 — `lms:sync-members` daily 01:30 + log |
| M5 | Dashboard widget thống kê LMS (chuyên cần/tiến độ) | ✅ | Sprint 8 — tab LMS trên Dashboard |
| M6 | Template khảo sát tái sử dụng cross-course | ✅ | Sprint 9 — survey-templates + apply |

### 4.4 Kỹ thuật

| # | Hạng mục | Mức | Ghi chú |
|---|----------|-----|---------|
| T1 | MAC Wi‑Fi **thiết bị** HV | — | **Không làm được** trên web; MAC AP + **IP CIDR (P0) + probe (P1) + GPS (P2)** |
| T2 | Queue/mail broadcast thông báo lớp | ✅ | Sprint 8 — `SendSystemNotificationEmail` queue |
| T3 | SCORM tracking (completion/score API 1.2) | ✅ | Sprint 9 — `LmsScormService` + commit |
| T4 | Proctor fullscreen + tab lock + timeline (**không** webcam/share) | ✅ | Sprint 9 |
| T5 | Role LMS mịn (assistant, grader) | ❌ | **Loại bỏ** — không làm |
| T6 | PHPUnit coverage LMS | ⚠️ partial | S6/S8/S9 + Campus + InstructorSchedule ✅; StandardHours vẫn thiếu test |
| T7 | Rate limit check-in / chat | ✅ | Sprint 8 — throttle 10/min check-in, 30/min chat |
| T8 | Retention file bài nộp | ✅ | Sprint 9 — `lms:prune-submissions` |
| T9 | Tom Select/Flatpickr | ✅ | Shell LMS auto-init mọi `select`; admin `tom-select-init` |
| T10 | Flash/alert native browser | ✅ | Đã thay bằng toast + modal; form xóa dùng `data-confirm` |
| T11 | TrustProxies + Test IP + CIDR validate | ✅ | Campus **P0** |
| T12 | LAN probe_url + QR token TTL/rotate | ✅ | Campus **P1** |
| T13 | GPS hard/soft/bypass + checkin events + stats | ✅ | Campus **P2** |

---

## 5. Cố ý ngoài phạm vi LMS

| Hạng mục | Module phụ trách |
|----------|------------------|
| CTĐT / môn / bài khung / lớp / phòng / GV master data | Subject, Class, Building, Instructor, … |
| Phân công giảng dạy khoa | TeachingAssignment |
| Sinh khung lịch / tiết | TrainingSchedule, ScheduleDetail |
| **Kê khai giờ chuẩn**, định mức, báo cáo giờ | **StandardHours** (Dashboard admin) |
| Thống kê lớp/GV tổng hợp đào tạo | Dashboard (không phải LMS analytics) |
| SSO / LDAP | Không (SPRINT5) |
| Mobile app native | Ngoài web |

**Lưu ý điều hướng GV:**  
- Lịch dạy hàng ngày → **LMS** `/lms/gv/schedule`  
- Kê khai giờ chuẩn → **Dashboard / StandardHours** (link navbar “Dashboard”)

---

## 6. Dashboard vs LMS — tránh nhầm

| Câu hỏi | Trả lời |
|---------|---------|
| Dashboard có phải LMS không? | **Không.** `/dashboard` = thống kê đào tạo + cửa vào admin. |
| Giờ chuẩn có trong LMS không? | **Không embed.** GV bấm Dashboard trên navbar LMS. |
| Điểm danh Wi‑Fi cấu hình ở đâu? | **Admin** sidebar «Wi‑Fi trường» — không portal GV. |
| Lịch HV/GV còn module cũ không? | Có module StudentSchedule / InstructorSchedule; portal LMS đã có lịch riêng và là hướng chính cho role đó. |

---

## 7. Backlog ưu tiên (cập nhật 2026-07-19)

### 7.1 LMS / portal — đã đóng

```
P0 — ✅ Sprint 6 (G1/G2/G3 + LmsSprint6TeachTest)
P1 — ✅ Sprint 7 (G4 KS · G5 CC · G6 alert · H1 deep-link notify)
P2 — ✅ Sprint 8 (M5/M1/M4/M2 + G7–G11 + T2/T7)
P3 — ✅ Sprint 9 (T3/T4*/M3/M6/H2/H3/T8/H4 light; T5/H5 loại bỏ)
```

### 7.2 Campus điểm danh web-only — đã đóng

```
Campus P0 — ✅ TrustProxies + Test IP + CIDR validate
Campus P1 — ✅ probe_url + QR TTL/rotate
Campus P2 — ✅ GPS + bypass + lms_checkin_events + /campus-network/stats
```

### 7.3 Việc mở (toàn project, không chỉ LMS)

| # | Việc | Ưu tiên | Ghi chú |
|---|------|---------|---------|
| B1 | Sửa/skip `InstructorScheduleTest` (InstructorFactory) | ✅ | 2026-07-20 — factory + calendar 7 ngày |
| B2 | PHPUnit golden **StandardHours** (calculation) | P1 | Business critical, chưa test |
| B3 | Smoke regression **export LHL** (file có template) | P1 | Xem `docs/TEMPLATE_DEPENDENCIES.md` |
| B4 | Runbook IT campus (TRUSTED_PROXIES, CIDR, probe, HTTPS GPS) | P1 | Code xong ≠ prod hiệu lực |
| B5 | Smoke tay 3 role staging | P2 | Checklist §9 + SMOKE_TEST_REPORT §3 |
| B6 | Hygiene secrets `.env.example` | P2 | Security |
| B7 | Thống nhất worktree git (tránh ship nhầm) | P2 | `nothign` vs `D:\CDHC2\...` |
| B8 | Mở rộng coverage PHPUnit LMS (check-in E2E auth full) | P3 | Đã có unit/feature campus |

**Ngoài phạm vi (giữ nguyên):** MAC thiết bị, video call, SSO, role grader mịn, proctor webcam.

---

## 8. File / route mốc (để dev không tạo trùng)

| Việc | Mở rộng từ |
|------|------------|
| Override điểm teach | `modules/Lms/Controllers/GradebookController.php` |
| Khảo sát teach | `SurveyController` + views `lms::surveys/*` |
| Chứng chỉ teach | `CertificateController` + `LmsCertificateService` |
| Alerts teach | `AlertController` + `LmsAlertService` |
| Sync HV lớp | `LmsCourseService::syncMembersFromCore` |
| Wi‑Fi / campus | `CampusNetworkController`, `CampusNetwork`, `LmsCampus`, `CheckinStats`, models Setting + CheckinEvent |
| Scope quyền khóa | `LmsAccess::canTeachCourse`, `LmsCourseService::queryForUser` |
| Portal teach routes | `modules/Lms/Routes/web.php` prefix `lms/gv` |
| Journal / template / smoke | `docs/CODE_JOURNAL.md`, `docs/TEMPLATE_DEPENDENCIES.md`, `docs/SMOKE_TEST_REPORT.md` |

---

## 9. Checklist “đã ship” nhanh (smoke)

- [x] Admin: `/lms` hub, CRUD khóa, gradebook override, cert issue, survey publish, campus-network  
- [x] HV: `/lms/hoc` khóa, lịch, profile, phòng học tabs, nộp BT, thi, điểm danh QR/self/GPS, chat/forum  
- [x] GV: `/lms/gv` → mode teach: soạn, chấm+ZIP, thi+CSV, điểm danh+QR/GPS+TTL, lớp, tương tác, lịch+deep-link  
- [x] UX: toast popup + confirm modal (thay banner/alert browser)  
- [x] UX: Tom Select / Flatpickr đồng bộ shell LMS  
- [x] GV: **override điểm** trên teach (Sprint 6)  
- [x] GV: soạn KS / cấp CC (đủ ĐK) / evaluate alert trên teach (Sprint 7)  
- [x] Báo cáo LMS multi-course (`lms.gradebook.export-multi` — Sprint 8 M2)  
- [x] Campus P0–P2 (Test IP, probe, QR TTL, GPS, stats)  
- [x] Tests PHPUnit LMS S6/S8/S9 + Campus (InstructorScheduleTest vẫn fail factory — B1)  
- [ ] Smoke tay 3 role trên staging (xem SMOKE_TEST_REPORT §3)  
- [ ] IT runbook campus trên mạng thật  

---

## 10. Smoke tự động gần nhất

Xem **`docs/SMOKE_TEST_REPORT.md`** (2026-07-19): 73 pass / 9 fail (`InstructorScheduleTest`); campus+LMS green; migrate P2 ran; login local 200.

---

*Cập nhật file này khi đóng gap: chuyển dòng từ §4 sang §2 hoặc đánh dấu ✅ trong checklist §9.  
Không duplicate FEATURE_SPEC giờ chuẩn — module StandardHours theo TODO.md riêng.  
Journal dài: `docs/CODE_JOURNAL.md`.*
