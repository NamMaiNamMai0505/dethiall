# SPRINT 6 — Kế hoạch triển khai tiếp theo (LMS Portal GV/HV & Vận hành)

> Handoff 2026-08-03: các Sprint 6–9 LMS trong tài liệu này đã hoàn tất theo
> backlog cũ. Phần việc tiếp theo xem `docs/ROADMAP_STATUS.md`; không mở lại các
> sprint này nếu không có regression hoặc yêu cầu nghiệp vụ mới.

- **Ngày tạo:** 2026-07-18
- **Project:** `D:\CDHC2\lich-hoc-cdhc2`
- **Vai trò file này:** chuyển mục "Gợi ý backlog" (§7) trong tài liệu inventory thành sprint cụ thể — có phạm vi, tiêu chí hoàn thành (DoD), và prompt khởi động cho Claude Code. Tiếp nối `SPRINT1–5` (core, đã ✅) và `SPRINT_GV.md` (GV‑1→GV‑8, đã ✅).
- **Tham chiếu:** tài liệu inventory (đặc biệt §3, §4, §7, §8), `PROMPT_START.md`, `CLAUDE.md`, `AI_CONTEXT.md`.

> **Ghi chú tên file:** mình gọi tắt tài liệu bạn đưa là *inventory* (theo tiêu đề "Inventory: đã có / còn thiếu") vì không thấy tên file gốc — đổi lại nếu repo bạn đặt tên khác (VD `INVENTORY.md`). Tên `SPRINT6.md` cho file này cũng chỉ là gợi ý, đổi số/tên theo đúng quy ước bạn đang dùng.

**Nguyên tắc bắt buộc cho mọi sprint bên dưới (nhắc lại từ PROMPT_START/CLAUDE.md):** reuse controller/service đã có, không viết trùng · business logic ở Service · mọi action mới gắn permission `module.action` + scope qua `LmsAccess::canTeachCourse` · không phá BUSINESS_RULES · xong sprint nào, cập nhật lại tài liệu inventory sprint đó (chuyển dòng §4→§2, tick §9).

---

## 0. Đối chiếu với inventory gốc — vài điều chỉnh trước khi chốt sprint

### 0.1 Các mục "đã có nhưng chỉ admin" (§3) map vào đâu

| # | Hạng mục | Xử lý |
|---|----------|-------|
| A1 | Override điểm | → G3, **Sprint 6** |
| A2 | Tạo/publish khảo sát | → G4, **Sprint 7** |
| A3 | Cấp chứng chỉ | → G5, **Sprint 7** (chờ chốt policy ở §1) |
| A4 | Evaluate/resolve alert | → G6, **Sprint 7** |
| A5 | Tạo khóa LMS | Không đưa vào sprint nào — cố ý chỉ admin, giữ nguyên |
| A6 | Sync members | Phần tự động → M4, **Sprint 8**. Phần bấm tay vẫn cố ý chỉ admin |
| A7 | Exam admin attempts UI | Không cần code — teach đã có trang lượt + CSV (GV‑4). Chỉ cần sửa dòng A7 trong inventory cho khớp thực tế |

### 0.2 Bổ sung so với danh sách cụ thể ở §7

Đối chiếu bảng gán mức ưu tiên ở §4 với danh sách liệt kê ở §7, vài mục đã gắn nhãn P2/P3 trong §4 nhưng rớt khỏi §7:

- **Thiếu ở nhóm P2:** G8, G9, G10, M5, T7 — đã bổ sung vào **Sprint 8**.
- **Thiếu ở nhóm P3:** H2, H3, M3, M6, T5, T8 — đã bổ sung vào **Sprint 9**.

Không đổi mức ưu tiên mục nào — chỉ đưa đủ những gì §4 đã tự gắn nhãn nhưng §7 liệt kê thiếu.

---

## 1. Quyết định nghiệp vụ cần chốt sớm (không phải việc code)

| Việc | Câu hỏi cần trả lời | Ảnh hưởng | Chặn sprint nào |
|------|----------------------|-----------|------------------|
| G5 — Cấp chứng chỉ | GV cấp thẳng hay chỉ đề xuất, admin duyệt? | Permission + UI nút bấm khác hẳn nhau | Sprint 7 |
| T5 — Role mịn (assistant/grader) | Có cần trong 1–2 sprint tới, hay để backlog dài hạn? | Đụng bảng permission toàn hệ thống — nên tách RFC riêng | Sprint 9 (không gấp) |

Gợi ý cá nhân cho G5: cho GV **đề xuất** (dùng lại `issueEligible` nhưng gắn trạng thái "pending"), admin duyệt cuối. An toàn hơn vì tránh GV tự cấp chứng chỉ sai tiêu chuẩn, và tận dụng luồng issueOne/issueEligible sẵn có mà không phải mở permission cấp chứng chỉ thật cho GV.

---

## Cách đọc các bảng "Việc cần làm" bên dưới

- **Reuse** — controller/service đã có, chỉ mở rộng route + view. Không tạo mới trừ khi ghi rõ.
- **Cỡ** — S/M/L: ước lượng độ phức tạp *tương đối*, dùng để sắp thứ tự trong sprint. Không phải cam kết thời gian.

---

## Sprint 6 — Đóng nốt nền tảng Thi & Điểm (P0)

**Mục tiêu:** hoàn thiện 2 tính năng lõi GV dùng hàng ngày (thi, điểm) đang dở dang, dựng lưới test an toàn trước khi mở thêm bề mặt permission ở các sprint sau.

| Gap | Việc | Reuse | Cỡ |
|---|---|---|---|
| G2 | Sửa / xóa / reorder câu hỏi NHCH (hiện chỉ có store) | `ExamController` | M |
| G1 | Chọn câu lẻ (multi-select) khi tạo đề, thay vì lấy cả bank | `ExamController` | S |
| G3 | Wire override điểm + refresh lên tab "Lớp học" của teach | `GradebookController@override/refresh` | S |
| T6 | Feature test: permission scope GV (403 ngoài khóa mình dạy) + luồng check-in | PHPUnit + `LmsDemoSeeder` | M |

**Thứ tự:** G2 → G1 → G3 → T6. Làm G2 trước G1 vì UI chọn câu ổn định hơn khi bank đã sửa/xóa/reorder được. T6 chạy sau cùng nhưng cover cả 3 mục trên, không phải việc tách rời.

**DoD:**
- [x] GV sửa/xóa/sắp xếp lại câu hỏi NHCH từ tab Thi online
- [x] Tạo đề cho phép tick chọn câu cụ thể, không bắt buộc lấy cả bank
- [x] Tab Lớp học có nút override điểm + refresh, hành vi/log giống admin
- [x] Test: `tests/Feature/LmsSprint6TeachTest.php` (scope 403 + override + tạo đề câu lẻ). *Lưu ý: môi trường hiện thiếu PDO sqlite → chạy local cần bật extension `pdo_sqlite`.*
- [x] Cập nhật inventory: A1 + G1/G2/G3 → ✅; §9 tách dòng override đã tick

**Prompt khởi động:**
```
Bắt đầu Sprint 6 trong SPRINT6.md. Thứ tự bắt buộc: G2 → G1 → G3 → T6.
Ràng buộc: chỉ mở rộng ExamController và GradebookController hiện có — không tạo controller/service mới.
Mọi route mới nằm trong modules/Lms/Routes/web.php prefix lms/gv, check qua LmsAccess::canTeachCourse.
Sau mỗi mục, chạy test liên quan trước khi sang mục kế tiếp.
Không động vào GV-6/GV-7 (đã ✅) ngoại trừ thêm nút gọi tới G3.
Xong hết, tự cập nhật tài liệu inventory theo DoD của Sprint 6 rồi báo tôi review.
```

---

## Sprint 7 — Đóng vòng "lớp học số" trên portal GV (P1)

**Điều kiện bắt đầu:** đã chốt policy G5 ở §1.

**Mục tiêu:** để GV tự chủ 3 vòng lặp còn lại của một khóa học (khảo sát, chứng chỉ, cảnh báo sớm) mà không phải nhờ admin.

| Gap | Việc | Reuse | Cỡ |
|---|---|---|---|
| G4 | Soạn + publish khảo sát từ teach | `SurveyController` | M |
| G5 | Đề xuất/cấp chứng chỉ theo policy đã chốt | `CertificateController` + `LmsCertificateService` | M |
| G6 | Evaluate/resolve cảnh báo | `AlertController` + `LmsAlertService` | S |
| H1 | Deep-link thông báo → đúng tab con (BT/thi/điểm danh) | URL `/lms/hoc/courses/{id}` đã có, thêm query tab | S |

G4/G5/G6/H1 độc lập nhau — có thể làm song song nếu có hơn 1 track.

**DoD:**
- [x] GV tạo + publish khảo sát mới từ tab Lớp học, không cần vào `/lms` admin
- [x] GV cấp chứng chỉ khi **đủ ĐK** (không force; admin vẫn force trên shell admin) — policy Sprint 7
- [x] GV quét + resolve alert kèm ghi chú
- [x] Thông báo lớp chọn `link_tab` → URL `?tab=…` khi HV bấm chuông
- [x] Cập nhật inventory G4/G5/G6/H1

**Prompt khởi động:**
```
Bắt đầu Sprint 7 trong SPRINT6.md.
Trước khi code G5: xác nhận policy cấp chứng chỉ đã chốt (GV đề xuất hay cấp thẳng) — nếu chưa thấy ghi trong CLAUDE.md/AI_CONTEXT.md thì dừng, hỏi lại tôi, đừng tự suy đoán.
G4 → G6 → H1 độc lập nhau, làm theo thứ tự thuận tiện.
Reuse triệt để: không tạo lại SurveyController/AlertController/CertificateController, chỉ thêm route + view phía teach.
```

---

## Sprint 8 — Vận hành, dữ liệu & polish (P2)

**Mục tiêu:** giảm việc tay chân của admin (sync, báo cáo, tạo khóa), vá các lỗ hổng nhỏ ảnh hưởng trải nghiệm hàng ngày, và chặn một rủi ro đang bỏ ngỏ (check-in không giới hạn tần suất).

**Vận hành admin**

| Gap | Việc | Cỡ |
|---|---|---|
| M5 | Widget thống kê LMS trên Dashboard (chuyên cần/tiến độ) — làm trước tiên vì read-only, rủi ro thấp, giá trị thấy ngay | S/M |
| M1 | Wizard tạo khóa (môn+lớp+GV+sync 1 bước) — lớp mỏng điều phối `CourseController` + `LmsCourseService` + `TeachingAssignment`, không viết business logic mới | L |
| M4 | Job đồng bộ members định kỳ, bọc quanh `LmsCourseService::syncMembersFromCore` | S |
| M2 | Báo cáo khoa / export điểm nhiều khóa | M |

**Polish GV/HV**

| Gap | Việc | Cỡ |
|---|---|---|
| G7 | Filter bài nộp "chờ chấm only" | S |
| G10 | Deep-link lịch khi 1 môn nhiều lớp — mở rộng logic deep-link đã có ở GV‑8 | S |
| G8 | Map `subject_lesson_id` bài LMS ↔ CTĐT — kiểm tra field model đã có chưa, chủ yếu là UI | S |
| G9 | HV nộp lại sau feedback | M |
| G11 | Feature test scope GV đầy đủ + trang 403 riêng cho LMS | S |

**Kỹ thuật/an toàn**

| Gap | Việc | Cỡ |
|---|---|---|
| T2 | Chuyển notify lớp từ insert `system_notifications` đồng bộ sang queue/mail | M |
| T7 | Rate limit check-in + chat (throttle middleware sẵn có của Laravel, không tự chế cơ chế mới) | S |

**DoD:**
- [x] Dashboard admin có widget LMS (chuyên cần/tiến độ theo khoa) — tab **LMS** + `LmsStatisticsService`
- [x] Tạo 1 khóa mới từ 0 chỉ qua wizard, không cần vào 3 màn hình rời — `lms.courses.create` 3 bước → `createWithMembers`
- [x] Sync members chạy tự động theo lịch, có log — `lms:sync-members --published` daily 01:30 → `storage/logs/lms-sync-members.log`
- [x] Export báo cáo điểm nhiều khóa ra 1 file — `/lms/gradebook/export-multi`
- [x] Check-in giới hạn tần suất hợp lý; chat có rate limit chống spam — throttle middleware
- [x] Cập nhật inventory: hầu hết §4.3 → ✅; G7/G8/G9/G10/G11 → ✅ (+ T2/T7)

**Prompt khởi động:**
```
Bắt đầu Sprint 8 trong SPRINT6.md.
Thứ tự nhóm: Vận hành admin (M5 trước tiên) → Polish GV/HV → Kỹ thuật/an toàn xen kẽ khi rảnh.
M1 (wizard) là lớp điều phối — không viết lại CourseController/LmsCourseService/TeachingAssignment.
T7: dùng middleware throttle chuẩn của Laravel, không tự chế cơ chế đếm request.
```

---

## Sprint 9 — Backlog dài hạn / nâng cao (P3)

**Mục tiêu:** giá trị thật nhưng không khẩn. **Chốt phạm vi 2026-07-18:**  
- **Làm:** T3, T4 (không webcam/screen share — fullscreen + tab lock + timeline), M3, M6, H2, H3, T8, H4 light (không offline submit queue).  
- **Loại bỏ:** **T5** role mịn, **H5** live video.

| Gap | Việc | Status |
|---|---|---|
| ~~T5~~ | Role mịn (assistant, grader) | ❌ **Loại bỏ** (không làm) |
| T3 | SCORM tracking (completion/score/suspend API 1.2) | [x] `LmsScormService` + commit route + player API |
| T4 | Proctor: fullscreen bắt buộc, rời tab/FS, timeline GV — **không** webcam/share | [x] exam-take + heartbeat + attempts timeline |
| M3 | Designer template chứng chỉ (layout_json + preview) | [x] admin certificates index |
| M6 | Template khảo sát cross-course | [x] `/lms/survey-templates` + apply vào khóa |
| H2 | UI điểm danh chi tiết IP/mạng cho HV | [x] calendar day detail |
| H3 | Versioning lần nộp bài | [x] `lms_assignment_submission_versions` + UI |
| T8 | Retention file bài nộp | [x] `lms:prune-submissions` monthly |
| H4 | PWA light (manifest + SW shell) — **không** hàng đợi nộp offline | [x] `manifest.webmanifest` + `sw-lms.js` |
| ~~H5~~ | Live video conference | ❌ **Loại bỏ** (không làm) |

**Cố định ngoài backlog — không đưa vào sprint nào:**
- **T1** — MAC Wi-Fi thiết bị HV: không khả thi trên web thuần.
- **A5/A6 (phần thủ công)** — tạo khóa + bấm sync tay vẫn cố ý chỉ admin.
- **T5 / H5** — đã chốt loại bỏ khỏi roadmap LMS hiện tại.

---

## Thứ tự & phụ thuộc tổng quát

```
Sprint 6 (P0) → Sprint 7 (P1, cần chốt policy G5) → Sprint 8 (P2) → Sprint 9 (P3, chọn lọc)
```

- T6 (test) không phải làm 1 lần rồi bỏ — mở rộng dần qua Sprint 7/8.
- Không mở G5 trong Sprint 7 nếu chưa có câu trả lời chính sách.
- Trong Sprint 8, nhóm "vận hành admin" và "polish GV/HV" độc lập, có thể xen kẽ nếu có 2 track.
- Sau mỗi sprint: đồng bộ lại tài liệu inventory trước khi mở sprint kế — tránh tài liệu trôi khỏi code thật.

## Bổ sung hoàn tất — Form chỉnh sửa lịch học (2026-08-02)

| Hạng mục | Trạng thái |
|---|---|
| Giữ nhóm nút Lùi ngày/Ngày tiếp theo trên cùng một hàng, có cuộn ngang khi màn hình hẹp | [x] |
| Giữ nút Copy/Xóa của từng tiết trên cùng hàng với nội dung tiết | [x] |
| Giữ nhóm Lưu/Lưu & Ngày tiếp theo không bị rơi dòng | [x] |

Phạm vi áp dụng: dùng chung cho toàn bộ form Thêm/Chỉnh sửa lịch tại `training-schedules/{id}/schedule-details/*`.

---

*File build-on-top của §7 trong tài liệu inventory. Khi đóng gap nào, quay lại inventory chuyển dòng đó từ §4 sang §2 và tick §9 trước khi tiếp tục sprint kế.*
