# Sprint 43 — Scope và UI hardening

## Đã triển khai

- Form Bài học dùng popup xác nhận đồng bộ với hệ thống, không còn native `confirm()`.
- CRUD Bài học kiểm tra lại phạm vi môn của Khoa qua `TrainingScheduleAccess`.
- Các route CRUD Bài học gắn permission riêng `subject-lessons.create/edit/delete`.
- Các role phân hệ Giờ chuẩn dùng permission ứng dụng riêng; không thừa hưởng quyền Lịch đào tạo.

## Kiểm thử

- PHP lint và Blade cache đạt.
- Route list CRUD Bài học đã kiểm tra.
- Feature test role foundation chạy đạt theo nhóm:
  - `test_dedicated_roles_have_separated_permissions` — 10 assertions.
  - `test_training_schedule_scope_requires_both_role_and_matching_unit` — 8 assertions.

## Trạng thái

- Đã hoàn tất phần nền tảng trong local: role phân hệ, permission matrix, CRUD bài học và
  liên kết bộ lọc Ngành → Môn → Bài.
- Toàn bộ Feature suite local đạt **157 tests / 1.176 assertions**.

## Theo dõi sau sprint

- Chạy toàn bộ test suite trên CI/staging trước khi merge.
- Tiếp tục rà các màn hình danh mục còn dùng bộ lọc độc lập và chuyển sang bộ lọc liên động chung.
