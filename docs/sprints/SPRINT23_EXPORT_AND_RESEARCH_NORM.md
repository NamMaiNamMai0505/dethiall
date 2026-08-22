# Sprint 23 — Chuẩn hóa xuất LHL và hiển thị định mức NCKH

## Kết quả

Sprint 23 hoàn thiện lớp giao diện và kiểm chứng hồi quy cho luồng xuất lịch, đồng thời đưa định mức nghiên cứu khoa học đến đúng màn hình kê khai của giảng viên.

### Giao diện xuất lịch

- Giữ nguyên tab và toàn bộ lựa chọn xuất để người dùng vẫn nhận biết lộ trình tính năng.
- Khóa tạm thời `Xuất lịch học (Word)` và `Xuất LHL (Excel)`; khi rê chuột sẽ hiển thị thông báo `Đang phát triển thêm`.
- `Xuất LHL (Word)` tiếp tục hoạt động cho Phòng Đào tạo và quản trị toàn hệ thống.
- `Xuất kế hoạch HL (Word)` tiếp tục hoạt động theo phạm vi Khoa.
- Các endpoint cũ vẫn được giữ để không phá vỡ tích hợp nội bộ và có thể mở lại chức năng sau này.

### Chia nhóm tiết trong LHL/KHHL

- Luồng xuất tự xác định layout cổ điển khi dữ liệu đúng hai buổi `1–5` và `6–9`.
- Khi lịch có nhóm tiết khác, hệ thống tự chuyển sang mẫu `lhl.training_plan.grouped_periods`; không thay thế mẫu cổ điển đang dùng.
- Đã kiểm chứng trường hợp `1–3 / 4–5 / 6–9`: nhóm `4–5` được giữ nguyên cùng môn học tương ứng, không bị bỏ qua hoặc nhập vào nhóm khác.
- Cả tầng tổng hợp dữ liệu và file Word tạo ra đều có test hồi quy cho nhóm tiết này.

### Định mức NCKH

- Trang danh sách và form kê khai NCKH của giảng viên hiển thị rõ định mức NCKH phải thực hiện.
- Thông tin gồm đối tượng, kỳ áp dụng và số giờ NCKH quy định.
- Với người quản lý, thẻ định mức cập nhật theo giảng viên được chọn trên form.
- Nếu giảng viên chưa được gán đối tượng hoặc chưa cấu hình định mức, giao diện đưa ra cảnh báo thay vì hiển thị số liệu gây hiểu nhầm.

## Cơ sở dữ liệu và triển khai

Sprint 23 không thêm migration. Định mức NCKH dùng trường `research_hours` của danh mục đối tượng và chế độ kỳ hiện hành của module Giờ chuẩn GV.

Nếu production chưa triển khai nền tảng role của Sprint 21–22 thì vẫn cần chạy các migration và lệnh chuyển đổi đã ghi trong tài liệu Sprint 22. Nếu đã triển khai rồi, Sprint 23 chỉ cần phát hành mã nguồn và xóa cache ứng dụng.

## Kiểm chứng

- Full suite: **192 tests, 1150 assertions**.
- Blade view cache thành công.
- Vite production build thành công.
- Pint và `git diff --check` thành công.
