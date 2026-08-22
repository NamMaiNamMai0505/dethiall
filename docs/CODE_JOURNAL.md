# Siêu nhật ký code — Quản lý đào tạo CDHC2

**Phiên bản journal:** 2026-07-19  
**Repo thực tế làm việc:** `D:\CDHC2\lich-hoc-cdhc2`  
**Stack:** Laravel 12 · PHP 8.4 · MySQL · Vite + Tailwind v4 · Spatie Permission/Backup · module custom `modules/*`

Tài liệu này **tổng hợp timeline theo migration, module, sprint LMS, git gần đây và epic session** (xuất LHL, campus P0–P2). Không thay git log; dùng để onboard và đối chiếu “đã ship gì”.

---

## 0. Bản đồ hiện trạng (một trang)

| Trụ | Entry | Tình trạng |
|-----|-------|------------|
| **A. Đào tạo lõi** | `/dashboard`, CRUD modules | Production-grade |
| **B. Giờ chuẩn** | `/standard-hours/*` | Module lớn, feature dày |
| **C. LMS** | `/lms`, `/lms/hoc`, `/lms/gv` | Sprint 1–9 + teach portal |
| **D. Campus check-in** | `/campus-network`, check-in HV | P0–P2 web-only |
| **E. Xuất lịch/HL** | Training schedule export tab | Excel template + Word |
| **F. Vận hành** | backup S3, notify, CI deploy | Có, vẫn polish |

---

## 1. Epoch 2025-07 → 2025-11 — Nền tảng lịch & RBAC

### 1.1 Master data & lịch (migrations 2025-07 → 08)

| Thời điểm (migration) | Nội dung |
|----------------------|----------|
| 2025-07 | `users`, `units`, `specializations`, `instructors`, `classes`, `training_schedules`, `subjects` |
| 2025-08 | `classrooms`, classroom_id, permission tables (Spatie), user info, buildings, teaching_assignments, schedule_details, lesson_type |
| 2025-09 | audit buildings, indexes schedule_details |
| 2025-10–11 | semester subjects, user class/instructor/type, exam_hours, soft-delete subject code, user code, instructor_id FK, permissions instructor-schedule / dashboards / student-schedule |

**Kết quả:** hệ **lập lịch huấn luyện + phân công + RBAC** — xương sống product gốc (TKB).

### 1.2 Module architecture

Custom modules (không nanobox):

`Authentication`, `Building`, `Class`, `Classroom`, `Dashboard`, `Home`, `Instructor`, `InstructorSchedule`, `ScheduleDetail`, `Specialization`, `Student`, `StudentSchedule`, `Subject`, `TeachingAssignment`, `TrainingSchedule`, `Unit`, `User`, `Trash`…

Pattern: `Controllers / Requests / Routes / Views / Providers` + `ModuleServiceProvider`.

---

## 2. Epoch 2026-07-08 → 07-14 — Giờ chuẩn + user ops + lịch màu

### 2.1 StandardHours (2026-07-08 → 07-18)

- Object types, positions, hour norms, research norms  
- Conversion / research categories + records  
- Yearly results, calculation logs  
- Research members, settings, hour exchange  
- Departments + overtime pools (07-18)  

→ **Epic giờ chuẩn giảng viên** (định mức → kê khai → tính → báo cáo). Docs `TT_06_2026_BQP.md`.

### 2.2 Hệ thống ngang

| Ngày | Nội dung |
|------|----------|
| 07-09 | `position_id` users |
| 07-10–11 | system_notifications + email fields |
| 07-12 | trash_logs |
| 07-14 | soft-delete users, ensure soft-deletes, **subject_lessons**, abbreviation subjects, **color + lesson** trên schedule flow |

### 2.3 Git gần (mid-July) — production firefight

- Fix 500 post-deploy, Turbo JS null, permissions StandardHours  
- Docker Vite include modules (Tailwind complete)  
- User import Tom Select, student import, trash  
- Training-schedules 500 (paginate, soft-delete, classroom col)  
- Mixed content HTTPS relative URLs  

---

## 3. Epoch 2026-07-16 → 07-18 — CTĐT sâu + LMS S1–9 + campus

### 3.1 CTĐT

- Extend subject_lessons + review hours  
- Faculty unit codes K1–K8  

### 3.2 LMS sprints (migrations)

| Migration | Sprint | Nội dung chính |
|-----------|--------|----------------|
| 07-17_100000 | S1 | courses, lessons, materials, members, enrollments… |
| 07-17_140000 | S2 | forum, chat, progress… |
| 07-17_160000 | S3 | assignments, exams, banks, attempts, proctor_events |
| 07-17_180000 | S4 | gradebook, attendance sessions/records, alerts… |
| 07-17_200000 | S5 | certificates, surveys… |
| 07-18_100000 | tweak | training_systems + LMS tweaks |
| 07-18_120000 | GPS/DM | checkin lat/lng, chat recipient |
| 07-18_140000 | Campus | `campus_network_settings`, network fields attendance |
| 07-18_160000 | S9 | SCORM track, proctor flags, submission versions, cert layout_json, survey templates, prune support |

### 3.3 Portal & teach (code, docs `LMS_GAPS` / `SPRINT_GV`)

| Sprint | Focus |
|--------|--------|
| S1–5 | Core LMS admin + learner |
| GV-1…8 | Portal GV `/lms/gv` + mode teach |
| S6 | NHCH CRUD, chọn câu, grade override teach |
| S7 | Survey/cert/alert wire teach, deep-link notify |
| S8 | Wizard, multi export, sync job, throttle, dashboard LMS, pending filter… |
| S9 | SCORM commit, proctor, cert designer JSON, survey templates, versions, prune, light PWA |

### 3.4 Tests LMS

- `LmsSprint6TeachTest`, `LmsSprint8OpsTest`, `LmsSprint9OpsTest`  

---

## 4. Epoch 2026-07-18/19 — Xuất LHL + chữ ký + điểm danh P0–P2

*(Nhiều phần uncommitted / session dài trên worktree; migration đã có trên disk.)*

### 4.1 Xuất lịch huấn luyện

- `config/lhl_export.php` — org title, signers, template path  
- `TrainingPlanFromTemplateExport` — load xlsx mẫu + **ZIP restore drawings** (gạch chéo)  
- `TrainingPlanWordExport` / Word A3 — slash PNG, merge header  
- `FacultyTrainingPlanExport` — kế hoạch khoa  
- UI calendar: tab xuất, collapsible, tick chữ ký  
- `DigitalSignature` model + service seed/claim theo tên  
- Routes: export training plan, faculty plan, schedule details  

### 4.2 Campus attendance web-only

| Layer | Migration / code | Ý |
|-------|------------------|---|
| **P0** | bootstrap TrustProxies; `CampusNetwork` validate/diagnose; Test IP UI | IP tin cậy + CIDR an toàn |
| **P1** | `2026_07_19_100000_campus_network_p1_*` | probe_url client, QR TTL, rotate token |
| **P2** | `2026_07_19_140000_campus_checkin_p2_*` | GPS hard/soft/bypass, `lms_checkin_events`, stats, Permissions-Policy geolocation=(self) |

Tests: `CampusNetworkP0/P1/P2Test`, `CampusNetworkTest`, `LmsCampusP2Test`.

---

## 5. Timeline tóm tắt (đọc nhanh)

```
2025-07     Nền users + đơn vị + lịch + môn + lớp
2025-08     Phòng học + RBAC Spatie + phân công + schedule_details
2025-09–11  Indexes, user types, permissions lịch HV/GV/dashboard
2026-07-08  StandardHours core
2026-07-09…14  Notifications, trash, soft-delete, subject_lessons, UX prod fixes
2026-07-16  CTĐT subject_lessons mở rộng
2026-07-17  LMS S1–S5 tables
2026-07-18  LMS GPS/DM, campus wifi, S9, departments overtime
2026-07-19  Digital signatures, campus P1/P2, LHL export polish (session)
```

---

## 6. Cây module “ai làm gì” (hiện tại)

| Module | Vai trò |
|--------|---------|
| TrainingSchedule | Khung lịch, calendar UI, **export LHL/Word/khoa** |
| ScheduleDetail | Tiết học chi tiết |
| Subject / Class / Instructor / … | Master data |
| StandardHours | Giờ chuẩn full stack |
| Lms | LMS admin + learn + teach + campus-network controllers |
| User | Users, roles, import, **digital signatures UI** |
| Dashboard | Thống kê đào tạo + widget LMS |
| Authentication | Login redirect theo role → LMS portals |

---

## 7. File “mốc” không được quên

| Path | Vì sao |
|------|--------|
| `bootstrap/providers.php` | Đăng ký module providers |
| `bootstrap/app.php` | Middleware, TrustProxies, schedule backup/sync |
| `app/Console/Commands/SyncPermissionsAndRoles.php` | Single source RBAC |
| `config/lhl_export.php` | Mẫu LHL + signers |
| `resources/templates/lhl/*.xlsx` | Template Excel hard dependency |
| `modules/Lms/Routes/web.php` | Toàn bộ LMS + campus routes |
| `modules/Lms/Support/CampusNetwork.php` | P0–P1 network logic |
| `modules/Lms/Support/LmsCampus.php` | P2 GPS campus |
| `modules/Lms/docs/LMS_GAPS.md` | Backlog LMS |
| `docs/TEMPLATE_DEPENDENCIES.md` | Feature phụ thuộc template |
| `docs/SMOKE_TEST_REPORT.md` | Smoke gần nhất |

---

## 8. Quyết định kiến trúc lặp lại (lessons)

1. **Reuse module** — LMS teach gọi service/controller sẵn, không fork.  
2. **Web không có SSID** — điểm danh = IP (+ probe + GPS), không MAC thiết bị.  
3. **Template Excel vs code-gen Word** — Excel bám file mẫu + ZIP; Word linh hoạt code.  
4. **Role portal** — HV/GV vào shell LMS; admin shell cho PDOT.  
5. **Giờ chuẩn tách LMS** — không embed; navbar “Dashboard” cho GV.  

---

## 9. Nợ kỹ thuật ghi trong journal

| Nợ | Mức |
|----|-----|
| `InstructorScheduleTest` thiếu `InstructorFactory` | Test đỏ (9 cases) |
| StandardHours gần như không PHPUnit | Cao nếu đụng công thức |
| Export LHL regression test | Trung |
| LMS_GAPS checklist từng lệch multi-course | Đã cập nhật 2026-07-19 |
| Worktree `nothign` vs `D:\CDHC2\...` lệch | Vận hành dev |
| Secrets / `.env.example` hygiene | Security |
| Prod login 468 từ môi trường dev | Edge/WAF |

---

## 10. Chỉ số smoke 2026-07-19

- PHPUnit: **73 pass / 9 fail** (fail = InstructorSchedule only)  
- Campus + LMS sprint tests: **all green**  
- Migrate: **đủ batch P2**  
- Login local: **200**  
- Template LHL: **có file**  

Chi tiết: [SMOKE_TEST_REPORT.md](./SMOKE_TEST_REPORT.md).

---

## 11. Liên kết tài liệu

| File | Nội dung |
|------|----------|
| [LMS_GAPS.md](../modules/Lms/docs/LMS_GAPS.md) | Backlog LMS đã/thiếu |
| [TEMPLATE_DEPENDENCIES.md](./TEMPLATE_DEPENDENCIES.md) | Feature phụ thuộc template |
| [SMOKE_TEST_REPORT.md](./SMOKE_TEST_REPORT.md) | Báo cáo smoke |
| `CLAUDE.md` / `PROMPT_START.md` | Quy ước agent & project |
| `FEATURE_SPEC.md` / `TODO.md` | Chủ yếu giờ chuẩn (legacy planning) |

---

*Journal nên append khi ship epic lớn (migration mới + 5–10 dòng “shipped”). Không ghi từng fix UI nhỏ.*

## 2026-08-04 (đợt 2) — Gom 8 vai trò, ma trận 6 phân hệ

- `ApplicationGate` — gác quyền theo ứng dụng cho LMS (13 controller) và `GradeAccess`.
- Registry 6 phân hệ / 62 ứng dụng: LMS và Quản lý điểm được liệt kê theo từng
  màn hình thật thay vì một dòng gộp.
- Còn 8 vai trò chuẩn; 4 vai trò chồng lấn đã gỡ, tài khoản chuyển tự động.
- Nối năm học Lịch đào tạo → LMS → Quản lý điểm (trước đây `lms_courses.academic_year_id`
  NULL 100%, kéo theo bảng điểm mất mốc năm học).
- Feature suite: **198 tests / 1.489 assertions**.

## 2026-08-04 — Làm lại Sprint 38–43 theo bảng phân quyền gốc

Bản Sprint 38–43 ngày 03-08 định nghĩa vai trò theo **phân hệ** nên Quản lý khoa
phải chọn giữa "quản lý lịch" và "quản lý giờ chuẩn", và vào Giờ chuẩn GV là full
quyền 15 ứng dụng. Làm lại theo `Bảng phân quyền vai trò.docx`:

- `app/Support/ApplicationRegistry.php` — nguồn duy nhất: phân hệ → ứng dụng →
  Xem/Thêm/Sửa/Xóa/Duyệt/Xuất (5 / 39 / 169).
- `app/Support/RoleCatalog.php` — vai trò theo **chức trách**, không theo phân hệ.
  Thêm `faculty-manager`, `research-agency-manager`.
- Gỡ fallback `standard-hours.index|create|edit` trong `StandardHoursBaseController`
  — đây là gốc của lỗi "full quyền".
- `x-filter-form` hỗ trợ `depends_on` → lọc liên động dùng chung toàn hệ thống.
- Hub Ngành đào tạo; `Mã số` thành khóa chính đứng cột đầu (BUSINESS_RULES đã cập nhật).
- Feature suite: **188 tests / 1.387 assertions**.

## 2026-08-03 — Roadmap handoff / Sprint 38–43

- Hoàn tất tách role quản lý lịch, ma trận quyền theo ứng dụng và granular permission Giờ chuẩn GV.
- Hoàn tất hub Ngành → Môn → Bài học, lọc liên động và CRUD bài học theo phạm vi khoa.
- Hardening tương thích role legacy, duyệt Giờ chuẩn và chặn role khoa cấu hình sai đơn vị.
- Feature suite đạt **157 tests / 1.176 assertions**.
- PR: [#157](https://github.com/tho076/lich-hoc-cdhc2/pull/157).
- Handoff chính thức: [`docs/ROADMAP_STATUS.md`](ROADMAP_STATUS.md).
