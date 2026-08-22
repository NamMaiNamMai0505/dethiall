# Sprint 28 — Business Relationship Map

## Đã triển khai

- Bảng `business_relationship_maps` lưu mapping nghiệp vụ có phiên bản trạng thái.
- Trang `/database-management/business` cho super admin.
- Tạo đề xuất liên kết giữa bảng/cột theo module Lịch huấn luyện, LMS, Giờ chuẩn và Điểm.
- Kiểm tra bảng nguồn/đích phải tồn tại trong schema.
- Mapping mới luôn ở trạng thái `proposed`, chưa được module sử dụng.

## Nguyên tắc

Foreign key vật lý và business mapping là hai lớp độc lập. Chỉ khi có bước review/publish riêng mapping mới được kích hoạt trong nghiệp vụ.
