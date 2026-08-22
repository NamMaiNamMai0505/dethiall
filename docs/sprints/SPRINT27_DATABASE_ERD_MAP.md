# Sprint 27 — ERD Schema Map

## Đã triển khai

- Trang `/database-management/map` dành cho `super-admin`.
- Hiển thị node cho từng bảng, danh sách cột và kiểu dữ liệu.
- Vẽ đường nối từ foreign key thật trong database.
- Kéo thả node để sắp xếp sơ đồ trên trình duyệt.
- Lọc node theo tên bảng.
- Không thay đổi schema và không chạy SQL trong sprint này.

## Nguyên tắc an toàn

Các đường nối hiện tại là quan hệ đã tồn tại. Mọi quan hệ kéo thả mới ở sprint sau sẽ được lưu dưới dạng đề xuất, phải kiểm tra dữ liệu và sinh migration trước khi publish.
