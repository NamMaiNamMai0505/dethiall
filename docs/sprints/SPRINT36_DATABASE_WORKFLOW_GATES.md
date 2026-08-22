# Sprint 36 — Migration Workflow Gates

## Đã triển khai

- Validate checksum và allowlist câu lệnh migration.
- Kiểm tra bảng nguồn/đích còn tồn tại.
- Reject migration draft/validated.
- Publish chỉ khi đã validated.
- Publish mặc định bị khóa bằng `DB_MANAGEMENT_ALLOW_PUBLISH=false`.
- Ghi audit khi publish metadata.
- SQL execution không chạy từ web; deployment pipeline chịu trách nhiệm thực thi sau staging/backup.
