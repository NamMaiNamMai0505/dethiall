# Sprint 37 — Release Workflow hoàn chỉnh

## Đã triển khai

- Staging validation report được lưu trên migration version.
- Backup gate trước publish.
- Publish chỉ khi version `validated` và có `backup_reference`.
- Rollback endpoint bị khóa nếu chưa bật `DB_MANAGEMENT_ALLOW_PUBLISH`.
- Kích hoạt Business Map chỉ sau khi có migration `published`.
- Audit log cho backup, publish và rollback.

## Lưu ý

SQL execution thực tế vẫn thuộc deployment pipeline; web chỉ quản lý trạng thái và kiểm soát điều kiện, tránh ALTER production ngoài quy trình release.
