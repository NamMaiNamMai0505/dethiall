# Runbook rollout role quản lý chuyên trách

Runbook này áp dụng cho chuỗi Sprint 20–24. Mục tiêu là đưa role quản lý mới lên production mà không cấp nhầm quyền, không mở sai phạm vi Khoa và vẫn có đường lui an toàn.

## 1. Nguyên tắc

- `management-roles:audit` luôn chỉ đọc, không sửa dữ liệu.
- `management-roles:transition` mặc định là dry-run; chỉ ghi khi có `--apply`.
- Triển khai thí điểm theo từng tài khoản trước khi chuyển toàn bộ.
- Không rollback riêng image cũ sau khi đã chuyển role nếu chưa phục hồi role cũ cho người dùng. Code cũ có thể không nhận diện các role chuyên trách mới.

## 2. Chuẩn bị

1. Sao lưu database và xác minh file backup có thể đọc được.
2. Ghi lại image/tag đang chạy để có thể rollback ứng dụng.
3. Chạy kiểm tra trước triển khai:

```bash
php artisan migrate:status
php artisan management-roles:audit --json
```

Trước khi migration Sprint 21 chạy, audit sẽ báo thiếu bốn role mới. Đây là kết quả dự kiến.

## 3. Triển khai schema và permission

```bash
php artisan migrate --force
php artisan permissions:sync
php artisan optimize:clear
```

Chạy audit lần hai:

```bash
php artisan management-roles:audit
```

Super Admin cũng có thể xem cùng báo cáo tại:

```text
/roles/integrity
```

Không chuyển role nếu còn các lỗi sau:

- `missing_role`, `missing_permissions`, `unexpected_permissions`;
- `training_office_not_classified`, `faculty_not_classified`;
- `invalid_faculty_code`, `duplicate_faculty_code`;
- `invalid_role_scope`, `conflicting_management_roles`, `role_link_mismatch`.

`legacy_manager` là cảnh báo dự kiến trước bước chuyển đổi.

`role_link_mismatch` không được transition tự động. Đây thường là dữ liệu cũ có
`role_id=manager` nhưng role thực tế của tài khoản là giảng viên/học viên. Phải
đối chiếu từng tài khoản trước khi sửa.

Nếu báo cáo đánh dấu “Có thể sửa an toàn”, hệ thống đã xác định tài khoản chỉ
có đúng một role thực tế. Xem trước và sửa theo từng tài khoản:

```bash
php artisan roles:repair-links --user=123
php artisan roles:repair-links --apply --user=123
php artisan management-roles:audit
```

Có thể chọn các tài khoản ngay trên trang `/roles/integrity`. Thao tác này chỉ
đồng bộ `users.role_id`; không thêm, xóa hoặc đổi role thực tế. Các lỗi
`role_assignment_missing` và `role_link_ambiguous` luôn phải xử lý thủ công.

## 4. Chuyển đổi theo đợt

Xem trước toàn bộ:

```bash
php artisan management-roles:transition
```

Thí điểm một tài khoản bằng ID, email hoặc mã tài khoản:

```bash
php artisan management-roles:transition --user=123
php artisan management-roles:transition --apply --user=123
php artisan management-roles:audit
```

Chỉ với tài khoản **nội bộ** đã xác minh là manager cũ nhưng bị thiếu bản ghi
trong bảng phân quyền, mới dùng tùy chọn sau và luôn giới hạn `--user`:

```bash
php artisan management-roles:transition --include-role-id-only --user=123
php artisan management-roles:transition --apply --include-role-id-only --user=123
```

Không dùng `--include-role-id-only` cho toàn bộ database.

Sau khi tài khoản thí điểm vượt qua smoke test, tiếp tục theo từng đơn vị. Chỉ khi danh sách dry-run đã đúng hoàn toàn mới chạy chuyển đổi toàn bộ:

```bash
php artisan management-roles:transition --apply
php artisan management-roles:audit --strict
```

`--strict` trả mã lỗi nếu còn cảnh báo hoặc lỗi, phù hợp để dùng làm quality gate thủ công sau rollout.

## 5. Smoke test bắt buộc

### Quản lý toàn hệ thống

- Vào được Dashboard và các module nghiệp vụ.
- Không được chỉnh role/permission lõi nếu không phải Super Admin.
- Không được override hồ sơ Giờ chuẩn đã duyệt.

### Phòng Đào tạo

- Tạo/sửa được khung môn, loại tiết và địa điểm.
- Không thể ghi bài học hoặc giảng viên bằng payload sửa tay.
- Xuất được LHL Word.

### Khoa K1–K8

- Chỉ thấy môn, bài học và giảng viên đúng Khoa.
- Không tạo được skeleton mới hoặc đổi môn/loại tiết/địa điểm.
- Chỉ gán được bài học và giảng viên vào skeleton của môn thuộc Khoa.
- Xuất được Kế hoạch huấn luyện Word.

### Quản lý Giờ chuẩn GV

- Vào được các màn hình kê khai, thẩm định và báo cáo Giờ chuẩn.
- Không có quyền sửa lịch học chi tiết.
- Form NCKH hiển thị đúng định mức của giảng viên được chọn.

### UI và xuất lịch

- Bộ lọc lịch giữ nguyên trang danh sách khi nhấn Enter hoặc nút Lọc.
- Tom Select mở rộng đủ để đọc nội dung và thu lại sau khi chọn.
- `Xuất lịch học Word` và `Xuất LHL Excel` vẫn hiển thị nhưng bị khóa với tooltip đang phát triển.
- LHL Word giữ đúng các nhóm `1–3 / 4–5 / 6–9`.

## 6. Rollback

### Chưa chạy `transition --apply`

Có thể rollback image ứng dụng. Các cột và role mới chưa gán cho người dùng không ảnh hưởng luồng cũ; không cần rollback migration ngay trong sự cố.

### Đã chạy `transition --apply`

Không rollback riêng image trước. Chọn một trong hai phương án:

1. Khuyến nghị: phục hồi database từ backup đã tạo ngay trước rollout, sau đó rollback image.
2. Nếu chỉ vài tài khoản thí điểm: gán lại role `manager` và `role_id` tương ứng bằng giao diện quản lý người dùng trên image mới, xác nhận đăng nhập, rồi mới rollback image.

Sau rollback luôn chạy:

```bash
php artisan optimize:clear
php artisan up
```

và lặp lại smoke test đăng nhập/permission tối thiểu.
