# Sprint 38 — Role Permission Foundation

## Đã triển khai

- Tách quyền mặc định của `standard-hours-manager` khỏi Lịch đào tạo.
- Giữ quyền Lịch đào tạo riêng cho `training-office-manager` và `faculty-schedule-manager`.
- Thêm migration đồng bộ lại quyền của ba role quản lý đã tồn tại.
- Chú thích trên form Thêm/Chỉnh sửa lịch được đưa xuống một dòng riêng, tránh bị box đè.

## Nguyên tắc

Migration chỉ cô lập ba role quản lý chuyên trách. Role `manager` cũ được giữ nguyên để không làm mất quyền ngoài dự kiến; việc chuyển tài khoản legacy sang role chuyên trách thực hiện sau khi quản trị viên rà ma trận.

## Phần tiếp theo

Sprint 39 sẽ xây Permission Matrix theo cấu trúc Phân hệ → Ứng dụng → Chức năng → Quyền.
