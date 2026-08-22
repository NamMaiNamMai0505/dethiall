# Sprint 22 — Khóa phạm vi lịch đào tạo ở server

## Kết quả

Sprint 22 đưa nền tảng role của Sprint 21 vào toàn bộ luồng đọc/ghi lịch đào tạo. Quyền trên giao diện chỉ còn là lớp hỗ trợ UX; server luôn kiểm tra lại role, loại đơn vị và phạm vi dữ liệu trước khi xử lý.

| Phạm vi | Được làm | Không được làm |
|---|---|---|
| Quản lý toàn hệ thống / Super Admin | Toàn bộ khung và phân công | — |
| Phòng Đào tạo | Tạo/sửa/xóa khung môn, loại tiết, phòng; xuất LHL | Không ghi lén bài học hoặc GV qua request |
| Khoa K1–K8 | Gán bài học và GV của chính Khoa trên khung đã có; xuất KHHL Khoa | Không tạo ngày/khung mới, đổi môn/loại/phòng, chọn GV Khoa khác hoặc xóa dữ liệu Khoa khác |
| Role cấu hình sai loại đơn vị | Không có dữ liệu quản lý | Không tự rơi về phạm vi toàn hệ thống |

## Các lớp bảo vệ đã áp dụng

- Mọi action tùy chỉnh và API lịch đều có permission middleware rõ ràng.
- Role quản lý lịch được kiểm tra đồng thời với `units.functional_type` và `units.faculty_code`.
- Ngày gửi lên phải đúng định dạng, nằm trong khoảng của lịch và khớp ngày trên URL.
- Payload tối đa 9 tiết, không trùng tiết và chỉ tham chiếu ID tồn tại.
- Khoa chỉ cập nhật các dòng skeleton đã được Phòng Đào tạo tạo trước; skeleton luôn được đọc lại từ DB thay vì tin hidden input.
- Xóa theo Khoa chỉ xóa `subject_lesson_id` và `instructor_id` của môn thuộc Khoa, không xóa khung hoặc môn Khoa khác.
- Môn, bài học, giảng viên, phân công giảng dạy, import CTĐT và các API dropdown đều được scope theo Khoa.
- Import khung CTĐT của Khoa từ chối file chứa mã môn không kết thúc bằng đúng K1–K8 được giao.
- Các API lịch cũ đã bỏ tham chiếu tới cột/relationship không tồn tại (`status`, `instructor_id`, `participants_count`...), tránh lỗi 500.

## Tương thích chuyển đổi

Role `manager` cũ vẫn giữ scope đơn vị ở Dashboard và các module cũ. Riêng thao tác quản lý lịch chỉ hoạt động khi đơn vị có thể xác định đúng là Phòng Đào tạo hoặc Khoa K1–K8. Công cụ chuyển đổi của Sprint 21 tiếp tục là đường nâng cấp chuẩn.

## Triển khai production

Sprint 22 dùng chung hai migration của Sprint 21, vì vậy môi trường chưa triển khai Sprint 21 cần chạy:

```bash
php artisan migrate --force
php artisan management-roles:transition
```

Lệnh transition mặc định là dry-run. Sau khi kiểm tra danh sách đề xuất:

```bash
php artisan management-roles:transition --apply
php artisan optimize:clear
```

Không cần migration bổ sung nếu hai migration `2026_07_31_000001` và `2026_07_31_000002` đã chạy.

## Kiểm chứng

- Full suite: **188 tests, 1119 assertions**.
- Có test hồi quy riêng cho cấu hình role sai, cấm tạo skeleton từ Khoa, cấm truy cập môn/GV chéo Khoa, giữ dữ liệu Khoa khác khi cập nhật/xóa và chống payload giả từ PDOT.
- Blade view cache và Vite production build thành công.
- Pint và `git diff --check` thành công.
