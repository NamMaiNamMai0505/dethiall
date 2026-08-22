# Database Management Hub — Release Guide

1. Chạy các migration mới trên staging.
2. Mở Integrity Audit và xử lý cảnh báo HIGH/MEDIUM.
3. Tạo migration version ở trạng thái draft.
4. Validate version và kiểm tra validation report.
5. Chạy Backup trên staging, xác nhận backup reference.
6. Chạy migration bằng deployment pipeline.
7. Chỉ bật `DB_MANAGEMENT_ALLOW_PUBLISH=true` trong thời gian publish có giám sát.
8. Nếu cần, rollback qua pipeline và ghi nhận audit.
9. Tắt lại cờ publish sau khi hoàn tất.

Web không tự chạy ALTER production; đây là chủ ý để tránh thay đổi schema ngoài quy trình release.
