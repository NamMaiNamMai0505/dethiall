# LMS Sprint 3 — Đánh giá học tập

**Phạm vi:** Bài tập, nộp bài, chấm online, ngân hàng đề, thi online, proctor cơ bản.

## Database
Migration `2026_07_17_160000_create_lms_sprint3_tables.php`:
- `lms_assignments` / `lms_assignment_submissions`
- `lms_question_banks` / `lms_questions`
- `lms_exams` / `lms_exam_questions` / `lms_exam_attempts`

## Routes
Admin: `/lms/courses/{course}/assignments|exams`
Learner: `/lms/hoc/courses/{course}/assignments|exams`

## UI
- Admin tạo BT, chấm submissions
- Admin NHCH + tạo thi từ bank
- HV nộp BT, làm thi (timer + proctor events JSON)

## Kiểm thử
1. `php artisan migrate`
2. Tạo bài tập → HV nộp → GV chấm
3. Tạo NHCH + câu MCQ → tạo thi → HV làm → xem điểm
