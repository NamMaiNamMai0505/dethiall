# Sprint 26 — Database Management Hub nền tảng

## Mục tiêu

Tạo trung tâm quản trị database an toàn, bắt đầu bằng catalog schema chỉ đọc.

## Đã triển khai

- Route `/database-management` dành riêng cho `super-admin`.
- Đọc danh sách bảng, cột, kiểu dữ liệu và khóa ngoại bằng Schema Builder của Laravel.
- Tìm kiếm theo tên bảng hoặc tên cột.
- API read-only `/database-management/schema` để dùng cho ERD ở sprint kế tiếp.
- Giao diện Dashboard Hub với trạng thái an toàn `READ ONLY`.
- Thêm mục truy cập vào sidebar Dashboard cho super admin.

## Chưa cho phép ở Sprint này

- Không chạy SQL tùy ý.
- Không sửa/xóa bản ghi.
- Không tạo hoặc xóa foreign key trực tiếp.
- Không tự động thay đổi schema production.

## Sprint tiếp theo

- ERD kéo thả quan hệ.
- Phân biệt foreign key đã tồn tại và liên kết đề xuất.
- Kiểm tra dữ liệu mồ côi trước khi tạo migration.
