# Sprint 21 — Nền tảng role quản lý chuyên trách

## Kết quả

Sprint 21 tách role quản lý theo đúng chức năng nhưng vẫn giữ `manager` cũ để production chuyển đổi an toàn:

| Role | Phạm vi |
|---|---|
| `system-manager` | Nghiệp vụ toàn hệ thống, không được quản trị role/quyền lõi và không có quyền override hồ sơ Giờ chuẩn đã duyệt |
| `training-office-manager` | Xếp khung lịch toàn trường; bắt buộc gắn đơn vị được phân loại Phòng Đào tạo |
| `faculty-schedule-manager` | Phân công bài học/GV đúng khoa; bắt buộc gắn đơn vị Khoa có `faculty_code` |
| `standard-hours-manager` | Quản lý, thẩm định Giờ chuẩn GV |
| `manager` | Role tương thích tạm thời, chưa bị xóa hoặc tự động thu hồi |

Đơn vị có thêm `functional_type` và `faculty_code`. Migration tự nhận diện `PHONG_DT`, `K1`–`K8`, `BKHQS` và `BKT&ĐBCLGDĐT`; các đơn vị khác được giữ là `other` cho đến khi quản trị viên phân loại.

## Chuyển đổi production

Sau khi deploy image mới:

```bash
php artisan migrate --force
php artisan management-roles:transition
```

Migration đã tạo và gán quyền cho các role mới. Chỉ chạy thêm `php artisan permissions:sync` nếu muốn đồng bộ lại toàn bộ ma trận quyền chuẩn của hệ thống (lệnh này cũng đồng bộ lại quyền các role cũ).

Lệnh cuối mặc định là **dry-run**, chỉ in đề xuất và không ghi dữ liệu. Sau khi rà bảng kết quả:

```bash
php artisan management-roles:transition --apply
php artisan optimize:clear
```

Có thể giới hạn theo ID, email hoặc mã tài khoản:

```bash
php artisan management-roles:transition --user=123 --user=user@example.com
php artisan management-roles:transition --apply --user=123
```

Manager thuộc đơn vị chưa được nhận diện sẽ luôn được giữ nguyên; công cụ không tự nâng bất kỳ tài khoản nào thành `system-manager`.

## Kiểm chứng

- Test migration, permission matrix, role + unit scope, guardrail và dry-run/apply.
- Full suite: 184 tests, 1091 assertions.
- Blade cache và Vite production build thành công.
