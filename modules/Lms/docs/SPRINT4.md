# LMS Sprint 4 — Theo dõi & quản lý học tập

**Ngày:** 2026-07-17  
**Project:** `D:\CDHC2\lich-hoc-cdhc2`

## Phạm vi

| Hạng mục | Mô tả |
|----------|--------|
| **Bảng điểm** | Tổng hợp BT / thi / chuyên cần / tiến độ; override điểm tổng kết; HV xem điểm cá nhân |
| **Điểm danh đa hình thức** | `manual` · `self` (cửa sổ thời gian) · `qr` (token) |
| **Lớp học phần độc lập** | `is_standalone`, `section_code`, `class_id` nullable; ghi danh email thủ công |
| **Tiến độ realtime** | Events khi mở bài/học liệu/SCORM; summary %; poll JSON 8s |
| **Cảnh báo học tập** | Quét: tiến độ thấp, chuyên cần <70%, BT quá hạn, điểm nguy cơ |

## Database
Migration `2026_07_17_180000_create_lms_sprint4_tables.php`

- Alter `lms_courses`: `is_standalone`, `section_code`, `class_id` nullable
- `lms_attendance_sessions` / `lms_attendance_records`
- `lms_progress_events` / `lms_progress_summaries`
- `lms_gradebook_rows`
- `lms_learning_alerts`

## Services
- `LmsGradebookService` — ma trận điểm, trọng số 40/40/10/10
- `LmsProgressService` — record + recompute
- `LmsAlertService` — evaluateCourse()

## Routes chính

| Admin | Learner |
|-------|---------|
| `lms.courses.gradebook` | `lms.learn.grades.my` |
| `lms.courses.attendance.*` | `lms.learn.attendance.*` |
| `lms.courses.progress.index` | `lms.learn.progress.*` (+ poll) |
| `lms.courses.alerts.*` | `lms.learn.alerts.*` |
| `lms.courses.members.*` | — |

## Kiểm thử thủ công
1. `php artisan migrate`
2. Tạo khóa **độc lập** → ghi danh email SV
3. Tạo buổi điểm danh self/QR → HV check-in
4. Mở bài/học liệu → xem tiến độ tăng (poll)
5. Bảng điểm: refresh snapshot → override điểm
6. Quét cảnh báo sau khi có tiến độ/chuyên cần thấp

## Ngoài phạm vi (Sprint 5)
Chứng chỉ, khảo sát CLĐT, SSO, đồng bộ SIS realtime.  
Không: CLO/PLO, Teams, Zoom, Calendar Sync.
