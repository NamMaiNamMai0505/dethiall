# Full Demo

## Cài mới hoàn toàn

```powershell
php artisan demo:install --fresh
```

Lệnh sẽ migrate lại database, seed core, tạo lịch huấn luyện, tài khoản LMS,
khóa học, bài học, học liệu, bài tập, bài thi, điểm danh, chat, forum, chứng chỉ
và dữ liệu lịch để test export. Bộ cài cũng tạo trọn luồng Giờ chuẩn giảng viên:
kê khai, minh chứng, duyệt, giảm trừ, quy đổi, tính giờ và vượt định mức.

## Tài khoản

Mật khẩu chung: `password`

| Portal | Tài khoản |
|---|---|
| Dashboard/Admin | `admin@example.com` |
| LMS Giảng viên | `giangvien@example.com` |
| LMS Giảng viên 2 | `gv2@example.com` |
| LMS Học viên | `hocvien@example.com` |
| LMS Học viên 2 | `hv2@example.com` |
| Giờ chuẩn — người duyệt khoa | `quanlykhoa@example.com` |

## Luồng test nhanh

1. Dashboard: lịch DEMO-LH-A/B → preview/export Word/Excel.
2. LMS giảng viên: khóa học → bài học → điểm danh → bài tập → bài thi → chat.
3. LMS học viên: học liệu → nộp bài → làm thi → xem điểm → lịch học.
4. Quản lý điểm: tạo/mở bảng điểm → nhập điểm → khóa/duyệt → export.
5. Quản lý mẫu xuất: upload → analyze → preview mock → binding → activate → export.
6. Giờ chuẩn: đăng nhập giảng viên để kê khai/gửi duyệt → đăng nhập quản lý khoa
   để duyệt → tính lại năm 2026-2027 → xem kết quả cá nhân và báo cáo.

Luồng liên thông mới: đăng nhập admin → `/lms/provisioning` → chọn lịch →
**Khởi tạo / đồng bộ**. Hệ thống tự lấy môn, lớp, giảng viên, học viên, khung bài
học và từng tiết để tạo khóa LMS cùng các buổi điểm danh. Trong bảng điểm LMS,
nút **Chuyển sang Quản lý điểm** chỉ ghi vào bảng nháp và không ghi đè bảng đã duyệt.

Hướng dẫn đầy đủ cho quản trị viên, giảng viên và học viên nằm tại
[`docs/HDSD_LMS.md`](HDSD_LMS.md).

## Demo Giờ chuẩn giảng viên

- Giảng viên: `giangvien@example.com` / `password`.
- Cấp trên trực tiếp: `quanlykhoa@example.com` / `password`.
- Giảng viên mẫu: Nguyễn Văn A (`GV-0001`), Khoa Điều dưỡng.
- Năm học mẫu: `2026-2027`.
- Điểm bắt đầu: `/standard-hours`.
- Hướng dẫn đầy đủ ngay trên web: `/standard-hours/guide`.

Dữ liệu có sẵn đủ bốn trạng thái Nháp, Đã gửi, Đã duyệt và Từ chối cho cả
Hoạt động chuyên môn lẫn NCKH; kèm hai file Word minh chứng hợp lệ. Lịch huấn
luyện demo cung cấp giờ đứng lớp, ngoài ra có giảm 5% định mức, quy đổi 30 giờ
NCKH sang HĐ chuyên môn, kết quả năm đã tính nhưng chưa khóa và pool vượt định
mức ở trạng thái nháp.

Tài liệu thao tác chi tiết dành cho cả hai vai trò nằm tại
[`docs/HDSD_STANDARD_HOURS.md`](HDSD_STANDARD_HOURS.md).

## Test tự chọn mẫu LHL theo nhóm tiết

Mở `/training-schedules/calendar` → tab **Xuất dữ liệu**, chọn lịch có mã
`DEMO-LHL-EXPORT-RANGES`. Seeder tạo **17 tuần / 85 ngày / hơn 700 tiết**,
trải trên khoảng bốn tháng và in khoảng ngày thực tế ra console. Dữ liệu gồm:

- `2–3 / 4–5 / 6–9` với `TTT / GPSL / TTT`.
- Một nhóm cắt buổi `3–4`.
- `1–3 / 4–5 / 6–9` với `TTT / GPSL / TTT`.
- Các biến thể `1–2`, tiết `5`, `6–8`, cùng hoạt động `NPL` và môn `ĐDCB`.

Excel và Word phải tự dùng feature `lhl.training_plan.grouped_periods`. Các lịch
chỉ có trọn buổi `1–5 / 6–9` vẫn dùng `lhl.training_plan` và mẫu cũ không bị thay thế.

Nếu database đã có dữ liệu, lệnh `demo:install` sẽ bỏ qua core seeder để tránh tạo
trùng; dùng `--fresh` khi cần môi trường demo sạch.
