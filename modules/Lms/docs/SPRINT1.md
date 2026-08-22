# LMS Sprint 1 — Nền tảng

**Ngày:** 2026-07-17  
**Phạm vi:** Module LMS học tập tích hợp hệ thống Quản lý đào tạo CDHC2 (không thay thế lịch / CTĐT / IAM).

## Đã giao

### Database
Migration `2026_07_17_100000_create_lms_tables.php`:

| Bảng | Mục đích |
|------|----------|
| `lms_courses` | Course: FK `subject_id`, `class_id`, `instructor_id` |
| `lms_course_members` | User ↔ course (`student` / `lecturer` / `assistant`) |
| `lms_lessons` | Bài trong course; optional FK `subject_lesson_id` |

### Models / Service
- `Modules\Lms\Models\LmsCourse`, `LmsCourseMember`, `LmsLesson`
- `LmsCourseService`: scope theo role, create + `syncMembersFromCore()` (SV theo `users.class_id`, GV theo teaching assignment / instructor)

### Routes (`/lms`)
- `lms.hub` — hub
- `lms.courses.*` — CRUD course + sync members
- `lms.courses.lessons.*` — CRUD lessons

### Permissions (Spatie)
`lms.index`, `lms.show`, `lms.create`, `lms.edit`, `lms.delete`, `lms.manage`

Gán:
- **super-admin:** all
- **manager:** index/show/create/edit (+ manage)
- **instructor / student:** index/show (xem khóa trong phạm vi)

### Navigation
- Home `/`: nút **LMS** cạnh Dashboard (khi đã đăng nhập + có `lms.index`)
- Sidebar admin: mục **LMS**

### Không đụng
IAM, lịch TKB, CTĐT, giờ chuẩn, trash, backup — chỉ **tham chiếu** môn/lớp/GV/SV.

## Kiểm thử thủ công
1. `php artisan migrate` + `php artisan permissions:sync`
2. Đăng nhập super-admin / manager → `/lms` → tạo course (chọn môn + lớp)
3. Kiểm tra members sau sync
4. Thêm lesson, map optional `subject_lesson`
5. Instructor/student chỉ thấy course thuộc phạm vi

## Sprint 2
Xem `SPRINT2.md` — portal học viên tách admin + materials/SCORM/forum/chat.
