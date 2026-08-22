# Builder Template Runbook

## Tạo mẫu

Trong Dashboard, LMS hoặc Quản lý điểm, chọn **Tạo bằng Builder**, chọn feature và định dạng Word/Excel. Các biến chỉ được chọn từ Data Catalog bằng Tom Select.

## Version và Active

Mỗi lần thay đổi lớn, chọn **Tạo version mới từ bản này**. Version mới luôn ở Draft; kiểm tra Preview/Export rồi mới Active. Chỉ version Active mới được dùng khi export thật.

## Collection/Table

Chọn `collection_key` cho bảng lặp, sau đó chọn binding cho từng cột. Ví dụ LHL dùng `schedule_groups[]`; bảng điểm có thể dùng collection tương ứng của provider điểm.

## PDF

PDF được tạo bằng LibreOffice từ file Word/Excel đã render. Biến môi trường cần có:

```env
LIBREOFFICE_BINARY="C:\\Program Files\\LibreOffice\\program\\soffice.exe"
LIBREOFFICE_TIMEOUT=120
```

## Tương thích mẫu upload cũ

Mẫu Word/Excel upload vẫn dùng parser và binding cũ. Builder Template không có file nguồn nên thao tác Download/OCR được ẩn; export dùng trực tiếp JSON schema.
