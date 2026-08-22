# Sprint 24 — Nghiệm thu và sẵn sàng production

## Mục tiêu

Khóa chuỗi thay đổi Sprint 20–23 bằng một lớp kiểm tra chỉ đọc trước rollout, test ma trận role/phạm vi và quy trình triển khai có điểm dừng, smoke test, rollback rõ ràng.

## Nội dung triển khai

### Công cụ audit production

Lệnh mới:

```bash
php artisan management-roles:audit
php artisan management-roles:audit --json
php artisan management-roles:audit --strict
```

Audit kiểm tra:

- Bốn role quản lý chuyên trách đã tồn tại.
- Permission của từng role khớp đúng ma trận, không thiếu và không dư.
- `PHONG_DT`, các Khoa K1–K8 và `faculty_code` được phân loại đúng.
- Một mã Khoa không bị gán cho nhiều đơn vị.
- Role chuyên trách chỉ gắn cho loại tài khoản và đơn vị hợp lệ.
- Không có role chuyên trách xung đột, lệch giữa `role_id` và bảng role.
- Liệt kê tài khoản còn `manager` cũ để chuyển đổi có kiểm soát.
- Ngăn transition hiểu nhầm giảng viên/học viên có `role_id=manager` bị tồn dư;
  dữ liệu chỉ có `role_id` chỉ được chuyển bằng tùy chọn tường minh và chỉ áp
  dụng cho người dùng nội bộ.

Lệnh không ghi database. Mặc định lệnh vẫn trả thành công để quản trị viên đọc báo cáo; `--strict` trả mã lỗi khi còn bất kỳ vấn đề nào.

### Kiểm thử

Test hồi quy bao phủ:

- Cấu hình sạch vượt qua strict audit.
- Role Khoa gắn nhầm đơn vị bị phát hiện.
- Role cũ được cảnh báo mà không bị tự động sửa.
- Permission bị lệch khỏi ma trận bị phát hiện.
- Hai đơn vị dùng trùng mã Khoa bị phát hiện.

### Vận hành

Runbook rollout chi tiết nằm tại `docs/runbooks/MANAGEMENT_ROLE_ROLLOUT.md`, bao gồm backup, migration, permission sync, dry-run, pilot theo tài khoản, audit strict, smoke test từng role và rollback.

## Database và CI

- Sprint 24 không thêm migration.
- Không thay đổi GitHub Actions hoặc quy trình deploy hiện có.
- Production vẫn phải chạy hai migration Sprint 21 nếu chưa triển khai.

## Kiểm chứng

- Full suite: **199 tests, 1185 assertions**.
- Nhóm test role/audit/scope: **16 tests, 98 assertions**.
- Composer audit: không có security advisory.
- NPM audit: không có vulnerability.
- Blade view cache và Vite production build thành công.
- Pint và `git diff --check` thành công.
