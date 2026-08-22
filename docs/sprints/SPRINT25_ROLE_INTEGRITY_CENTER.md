# Sprint 25 — Trung tâm sức khỏe phân quyền

## Mục tiêu

Đưa kết quả audit Sprint 24 thành công cụ vận hành trực quan và cung cấp đường sửa dữ liệu `role_id` an toàn, không yêu cầu quản trị viên can thiệp SQL trực tiếp.

## Nội dung triển khai

### Service dùng chung

Logic kiểm tra được chuyển vào `ManagementRoleIntegrityService` để Console Command và giao diện web dùng cùng một nguồn kết quả. Service quét role, permission, đơn vị, người dùng theo từng chunk và phân loại rõ lỗi có thể sửa tự động hay phải xử lý thủ công.

### Trang Sức khỏe phân quyền

- Đường dẫn: `/roles/integrity`.
- Chỉ Super Admin truy cập.
- Có lối vào từ Hub quản lý tài khoản và trang Vai trò & phân quyền.
- Hiển thị số role, đơn vị, tài khoản đã quét, lỗi, cảnh báo và số liên kết có thể sửa an toàn.
- Hiển thị bảng chi tiết theo mức độ, mã kiểm tra và đối tượng.
- Cho phép chọn từng tài khoản đủ điều kiện rồi đồng bộ bằng popup xác nhận chung của Dashboard.

### Nguyên tắc sửa an toàn

Hệ thống chỉ sửa `users.role_id` khi tài khoản có đúng **một** role thực tế trong bảng phân quyền. Thao tác này:

- không gán role mới;
- không xóa role;
- không thay đổi permission;
- không đoán khi tài khoản có nhiều role;
- ghi log actor, tài khoản, role cũ và role mới.

Các lỗi thiếu role thực tế hoặc có nhiều role mơ hồ luôn được giữ lại để Super Admin xử lý thủ công.

### Dòng lệnh

```bash
php artisan roles:repair-links
php artisan roles:repair-links --user=123
php artisan roles:repair-links --apply --user=123
php artisan roles:repair-links --json
```

Lệnh mặc định là dry-run. `--apply` chỉ ghi các ứng viên đã vượt qua điều kiện an toàn tại thời điểm transaction khóa bản ghi.

## Database và permission

- Không có migration mới.
- Không có permission mới.
- Route web nằm trong nhóm `role:super-admin` hiện có.
- Không thay đổi GitHub Actions.

## Kiểm chứng

- Full suite: **203 tests, 1212 assertions**.
- Nhóm test role/integrity/scope: **20 tests, 125 assertions**.
- Composer audit: không có security advisory.
- NPM audit: không có vulnerability.
- Blade view cache và Vite production build thành công.
- Pint và `git diff --check` thành công.

Audit database local hiện nhận diện ba liên kết có thể sửa an toàn và hai mục
cần rà thủ công. Sprint không tự áp dụng sửa để Super Admin có thể kiểm tra và
thử luồng chọn tài khoản trên trang mới.
