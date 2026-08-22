# Template Engine vận hành

## Theo dõi

Mỗi lần render thành công ghi audit `export.rendered` với `feature_key`, format, version,
`duration_ms` và `memory_peak_mb`. Khi render lỗi, exporter ghi `export.fallback` và tiếp tục
dùng exporter legacy nếu feature cho phép fallback.

## Xử lý template lỗi

1. Xác định `feature_key`, format và `template_version_id` từ audit log.
2. Deactivate version lỗi; không xóa file version đang được tham chiếu.
3. Kích hoạt version đã kiểm thử hoặc bật fallback legacy.
4. Render lại bằng mock data trước khi cho phép export dữ liệu thật.

## File tạm và archive

- File render chỉ được xóa sau khi response đã gửi xong.
- File template version không được xóa vật lý khi archive; giữ để rollback/audit.
- Job dọn file tạm phải giới hạn theo prefix `lms-template-` và tuổi file, không quét toàn storage.

## Chuyển PDF

`DocumentConverterInterface` hiện được bind tới `LibreOfficeDocumentConverter`.
Worker/container cần có binary `soffice`; có thể cấu hình bằng `LIBREOFFICE_BINARY` và
`LIBREOFFICE_TIMEOUT`. Converter chỉ nhận Office/ODS và xuất PDF, chạy bằng argv riêng
không qua shell string, có timeout và không ghi đè file nguồn.

## Release/UAT

- Chạy test, view cache, frontend build và dependency audit.
- Đối chiếu [LHL Golden Checklist](../reference/LHL_GOLDEN_CHECKLIST.md).
- Chỉ deprecate legacy exporter sau khi có xác nhận Word và Excel từ nghiệp vụ.
