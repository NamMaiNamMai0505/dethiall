# LMS Sprint 5 — Hoàn thiện hệ sinh thái (không SSO)

**Ngày:** 2026-07-17  
**Project:** `D:\CDHC2\lich-hoc-cdhc2`

## Phạm vi đã làm
| Hạng mục | Mô tả |
|----------|--------|
| **Chứng chỉ** | Mẫu + điều kiện (điểm / tiến độ / khảo sát); cấp tay / cấp HV đủ điều kiện / HV tự nhận; trang in; xác minh public |
| **Khảo sát CLĐT** | Tạo survey + câu rating/mcq/text; HV gửi; admin xem TB thang điểm |
| **SSO** | **Không** triển khai (theo yêu cầu) |
| **SIS realtime** | Không (có thể bổ sung sau bằng API/export) |

## Migration
`2026_07_17_200000_create_lms_sprint5_tables.php`

## Demo data
```bash
php artisan migrate
php artisan db:seed --class=LmsDemoSeeder
```

### Tài khoản test
| Vai trò | Email | Mật khẩu |
|---------|-------|----------|
| Super-admin | `admin@example.com` | `password` |
| Học viên 1 (đủ data) | `hocvien@example.com` | `password` |
| Học viên 2 (tiến độ thấp) | `hv2@example.com` | `password` |

Khóa demo: **LMS-DEMO-001** — admin `/lms/courses/{id}`, HV `/lms/hoc`.

## PPTX
Trình duyệt **không** render PowerPoint native. Upload PPTX → tải về hoặc Office Viewer (cần URL public).  
**Khuyến nghị:** xuất PDF để trình chiếu full inline trên LMS. SCORM/PDF/video/ảnh xem trực tiếp được.
