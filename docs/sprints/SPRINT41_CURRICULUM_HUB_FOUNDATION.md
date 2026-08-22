# Sprint 41 — Nền tảng Hub Hệ–Ngành–Môn–Bài

## Đã triển khai

- Permission Matrix đã có nhóm ứng dụng nghiệp vụ chuẩn bị cho Hub.
- Màn hình Bài học hiển thị danh sách bài của môn đang chọn, không lặp lại cột Môn/Mã môn trong bảng.
- Khi chọn Ngành, danh sách Môn được lọc theo Ngành ngay trên form.
- Khi chọn Môn, form tự tải danh sách bài tương ứng.
- Hiển thị tiêu đề Môn đang chọn phía trên bảng kết quả.
- Backend vẫn ràng buộc `specialization_id → subject_id` để không lấy bài ngoài ngành.

## Phần tiếp theo

- Đưa Môn học/Bài học vào Hub Ngành đào tạo.
- Thêm CRUD bài học trực tiếp.
- Đồng bộ bộ lọc liên động cho màn hình Môn học và các màn hình danh mục khác.
