# Hướng dẫn sử dụng LMS liên thông

## 1. Nguyên tắc dữ liệu

- Môn học và khung bài học được quản lý tại phân hệ đào tạo, không tạo lại trong LMS.
- Lớp và học viên lấy từ danh sách lớp hiện hành.
- Giảng viên lấy đúng từ từng tiết trong lịch đào tạo; khoa chỉ thao tác dữ liệu thuộc khoa mình.
- Một môn trong một lịch đào tạo tạo đúng một khóa LMS. Chạy đồng bộ nhiều lần không sinh bản trùng.
- Nội dung giảng viên viết thêm trong LMS được giữ lại khi khung bài học nguồn thay đổi hoặc bị xóa.

## 2. Quản trị viên và cán bộ quản lý

1. Mở **LMS → Khởi tạo từ lịch đào tạo** hoặc truy cập `/lms/provisioning`.
2. Lọc theo tên lịch, lớp hoặc năm học; đánh dấu một hay nhiều lịch.
3. Giữ lựa chọn **Đồng bộ luôn bài học và buổi điểm danh** rồi bấm khởi tạo.
4. Mở danh sách khóa học để kiểm tra môn, lớp, giảng viên, số bài và thành viên.
5. Khi lịch hoặc chương trình môn thay đổi, bấm **Đồng bộ bài & lịch** tại khóa học. Roster có thể đồng bộ riêng bằng **Đồng bộ TV**.

Hệ thống cũng tự chạy mỗi ngày: tạo/cập nhật khóa lúc 01:10 và đồng bộ roster lúc 01:30. Có thể kiểm tra trước bằng:

```powershell
php artisan lms:provision-courses --dry-run
php artisan lms:sync-members --published --dry-run
```

## 3. Giảng viên

1. Đăng nhập và mở `/lms/gv`; chỉ các khóa được phân công mới xuất hiện.
2. **Soạn bài**: bổ sung nội dung cho bài lấy từ chương trình, tải học liệu hoặc SCORM và công bố khi sẵn sàng.
3. **Bài tập**: tạo hạn nộp, xem từng phiên bản bài nộp, tải file an toàn và chấm/nhận xét.
4. **Thi online**: tạo ngân hàng câu hỏi, đề thi, thời lượng và số lượt làm.
5. **Điểm danh**: các buổi gắn nhãn “Từ lịch tiết …” được sinh từ lịch; mở buổi để điểm thủ công, QR/Wi-Fi hoặc GPS.
6. **Bảng điểm lớp**: làm mới snapshot, nhập điểm tổng kết ngoại lệ nếu cần. Công thức nằm trong **Cài đặt LMS**.
7. **Chuyển Quản lý điểm**: tạo/cập nhật cột “Điểm tổng hợp LMS” trong bảng nháp. Bảng khóa, chờ duyệt hoặc đã duyệt không thể bị LMS ghi đè.

## 4. Học viên

1. Mở `/lms/hoc` để xem các khóa của lớp mình.
2. Theo dõi bài học, học liệu, lịch, tiến độ và thông báo.
3. Nộp bài và tải lại file của chính mình. File bài nộp được lưu riêng tư; người ngoài khóa không thể truy cập URL công khai.
4. Làm bài thi, điểm danh trong thời gian giảng viên mở và xem kết quả cá nhân.
5. Hoàn thành khảo sát/điều kiện để nhận chứng chỉ nếu khóa có cấu hình.

## 5. Dữ liệu demo

Chạy trên môi trường thử nghiệm:

```powershell
php artisan demo:install --fresh
```

Mật khẩu chung là `password`:

| Vai trò | Tài khoản |
|---|---|
| Quản trị | `admin@example.com` |
| Giảng viên | `giangvien@example.com` |
| Giảng viên thứ hai | `gv2@example.com` |
| Học viên | `hocvien@example.com` |
| Học viên thứ hai | `hv2@example.com` |

## 6. Triển khai và vận hành

Sau khi cập nhật mã nguồn phải chạy migration. Với dữ liệu bài nộp cũ từng nằm trên public disk, chạy một lần:

```powershell
php artisan migrate --force
php artisan lms:migrate-submissions-private --dry-run
php artisan lms:migrate-submissions-private
php artisan optimize:clear
```

Các log nền quan trọng nằm tại `storage/logs/lms-provision-courses.log`, `lms-sync-members.log` và log Laravel chung.
