# LMS Sprint 2 — Nội dung học tập + tách portal

**Ngày:** 2026-07-17

## Kiến trúc giao diện

| Đối tượng | Shell | Entry |
|-----------|--------|--------|
| Super-admin / Manager (PDOT, Khoa) | `layouts.admin` | `/lms` hub quản trị |
| Sinh viên / Giảng viên | `layouts.lms-learner` (cổng học tập riêng) | `/lms/hoc` |

- Nút **LMS** trên trang `/`: admin → hub; SV/GV → `/lms/hoc`.
- Sidebar admin **chỉ** hiện LMS cho shell admin (manager/super-admin).
- Sau này chuyển lịch SV/GV sang portal học tập mà không dính dashboard quản trị.

## Database (migration `2026_07_17_140000_create_lms_sprint2_tables`)

- `lms_materials` — file học liệu (pdf/slide/video/document/image/archive/scorm)
- `lms_scorm_packages` — extract ZIP + launch path từ `imsmanifest.xml`
- `lms_forum_topics` / `lms_forum_replies`
- `lms_chat_messages` — chat theo course (poll HTTP)

Disk: `public` (`storage/app/public/lms/...`). Cần `php artisan storage:link`.

## Tính năng

1. **Upload tài liệu** — nhiều loại file, gắn optional lesson, max 100MB  
2. **SCORM** — upload ZIP, extract, parse launch href, iframe player ở portal  
3. **Diễn đàn** — topic + reply theo course (admin + learner views)  
4. **Chat** — gửi tin + poll 4s  

## Routes chính

- Learner: `lms.learn.*` dưới `/lms/hoc/*`
- Admin materials: `lms.courses.materials.*`
- Forum/chat: `lms.courses.forum.*` / `lms.courses.chat.*` và bản learn tương ứng

## Kiểm thử

1. `php artisan migrate` + `storage:link`
2. Manager: `/lms` → course → Tài liệu → upload PDF + SCORM zip  
3. Student: `/` → LMS → chỉ thấy cổng học, mở course / diễn đàn / chat  
4. Instructor: tương tự portal học (không sidebar admin LMS)

## Sprint 3 (kế tiếp)

Bài tập, nộp bài, chấm, ngân hàng đề, thi online, proctor cơ bản.
