# Sprint 40 — Quyền chi tiết theo ứng dụng Giờ chuẩn GV

## Đã triển khai

- Bổ sung permission theo từng ứng dụng nghiệp vụ:
  - Đối tượng, Chức danh, Bộ môn.
  - Vượt định mức, Giảm trừ định mức.
  - HĐCM, NCKH, Hoạt động ngoài HĐCM.
  - Tính giờ chuẩn, Báo cáo, Quyết định bù giờ, Cài đặt.
- `StandardHoursBaseController` không còn dùng permission module chung cho các controller con.
- Middleware hiện kiểm tra quyền `view`, `manage`, `approve`, `run`, `export` theo đúng ứng dụng.
- Cập nhật Request authorize, ApprovalAgency và giao diện hub để dùng quyền chi tiết.
- Ma trận vai trò hiển thị các permission nhiều cấp theo đúng nhóm ứng dụng.
- Có migration tạo permission và gán quyền tương thích cho các role hiện có.

## Kiểm tra

- PHP lint đạt.
- Blade cache đạt.
- Migration `2026_08_03_000002` migrate pretend đạt.
- Không còn middleware/controller/view Giờ chuẩn dùng quyền chung cho các thao tác nghiệp vụ chính.

## Lưu ý triển khai

Production cần chạy:

```bash
php artisan migrate
php artisan permission:cache-reset
```
