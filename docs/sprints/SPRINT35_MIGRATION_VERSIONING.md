# Sprint 35 — Migration Versioning

## Đã triển khai

- Bảng `database_migration_versions` lưu phiên bản migration sinh từ Business Map.
- Tạo snapshot `draft` kèm `up_sql`, `down_sql`, checksum và người tạo.
- Hiển thị danh sách version trong Migration Designer.
- Chưa publish/chạy SQL thay đổi schema; staging validation và approval vẫn là bước kế tiếp.
