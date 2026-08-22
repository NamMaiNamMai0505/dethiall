# Hướng dẫn sử dụng Giờ chuẩn giảng viên

## 1. Bộ demo

Chạy lệnh sau tại thư mục project:

```powershell
php artisan demo:install
```

Nếu cần tạo lại toàn bộ database sạch:

```powershell
php artisan demo:install --fresh
```

> `--fresh` xóa dữ liệu hiện có. Chỉ dùng cho môi trường demo.

Mật khẩu chung: `password`.

| Vai trò | Tài khoản | Phạm vi |
|---|---|---|
| Giảng viên | `giangvien@example.com` | Nguyễn Văn A, mã `GV-0001` |
| Cấp trên/người duyệt | `quanlykhoa@example.com` | Khoa Điều dưỡng và các đơn vị con |

Truy cập `/standard-hours`. Hướng dẫn trực quan trong ứng dụng nằm tại
`/standard-hours/guide`.

Năm demo là `2026`. Dữ liệu gồm giờ trực tiếp giảng dạy tự động từ lịch huấn luyện, kê khai
HĐ chuyên môn và NCKH ở đủ trạng thái, file minh chứng Word, giảm trừ 5%, một
lần quy đổi 30 giờ và kết quả tính sẵn chưa khóa.

## 2. Luồng hoạt động

```text
Giảng viên kê khai
        ↓
Lưu nháp hoặc gửi duyệt
        ↓
Cấp trên kiểm tra minh chứng
        ↓
Duyệt hoặc từ chối
        ↓
Tính giờ chuẩn (chỉ dùng hồ sơ đã duyệt)
        ↓
Đối chiếu, báo cáo và khóa kết quả
```

Các trạng thái:

| Trạng thái | Ý nghĩa | Giảng viên được làm gì |
|---|---|---|
| Nháp | Chưa gửi cấp trên | Sửa, xóa, gửi duyệt |
| Đã gửi | Đang chờ xử lý | Chỉ xem |
| Đã duyệt | Được tính vào kết quả | Chỉ xem |
| Từ chối | Minh chứng/nội dung chưa đạt | Sửa và gửi lại |

## 3. Hướng dẫn cho giảng viên

### 3.1 Kê khai hoạt động chuyên môn

1. Đăng nhập `giangvien@example.com`.
2. Mở **Giờ chuẩn GV → Kê khai HĐ CM**.
3. Chọn **Thêm kê khai**.
4. Chọn danh mục hoạt động, nhập tên hoạt động, ngày, năm và số lượng.
5. Kiểm tra số giờ hệ thống tự quy đổi theo công thức của danh mục.
6. Tải file minh chứng PDF, ảnh hoặc Word, tối đa 5 MB.
7. Chọn **Lưu nháp** để sửa sau hoặc **Lưu & Gửi duyệt** để chuyển cho cấp trên.

Demo có sẵn:

- Hai hồ sơ đã duyệt.
- Một hồ sơ đang chờ duyệt.
- Một hồ sơ nháp để thử sửa/gửi.
- Một hồ sơ bị từ chối để thử bổ sung minh chứng và gửi lại.

### 3.2 Kê khai nghiên cứu khoa học

1. Mở **Giờ chuẩn GV → Kê khai NCKH → Thêm kê khai**.
2. Chọn loại sản phẩm NCKH.
3. Nhập tên sản phẩm, vai trò, nơi công bố/nghiệm thu, ngày và thời gian thực hiện.
4. Thêm các thành viên tham gia; người khai luôn là thành viên đầu tiên.
5. Nhập tỷ lệ đóng góp khi biểu mẫu yêu cầu và kiểm tra giờ xem trước.
6. Tải minh chứng rồi lưu nháp hoặc gửi duyệt.

Nguyên tắc phân bổ mặc định:

- Một người: 100%.
- Hai người: 2/3 cho chủ trì, 1/3 cho thành viên.
- Ba người: 1/2 cho chủ trì, mỗi thành viên 1/4.
- Từ bốn người: theo tỷ lệ đóng góp khai báo.

Demo NCKH cũng có đủ Nháp, Đã gửi, Đã duyệt và Từ chối.

### 3.3 Sửa hồ sơ bị từ chối

1. Lọc trạng thái **Từ chối**.
2. Mở chi tiết hồ sơ và chọn **Chỉnh sửa**.
3. Sửa nội dung hoặc thay minh chứng.
4. Lưu lại, sau đó chọn **Gửi duyệt**.
5. Hồ sơ chuyển về trạng thái **Đã gửi**.

### 3.4 Xem kết quả

1. Mở **Giờ chuẩn GV → Kết quả giờ chuẩn**.
2. Chọn năm `2026`.
3. Kiểm tra giờ trực tiếp giảng dạy, HĐ chuyên môn, tổng giờ chuẩn, NCKH và định mức.
4. Mở chi tiết để xem từng điều kiện đạt/không đạt.
5. Dùng **Xuất Excel** khi cần lưu thống kê cá nhân.

Giảng viên không thể xem hồ sơ của người khác, không thể tự duyệt và không thể
chạy hoặc khóa phép tính.

## 4. Hướng dẫn cho cấp trên/người duyệt

### 4.1 Duyệt hoạt động chuyên môn

1. Đăng nhập `quanlykhoa@example.com`.
2. Mở **Kê khai HĐ CM**.
3. Lọc trạng thái **Đã gửi** và năm `2026`.
4. Mở hồ sơ, đối chiếu danh mục, số lượng, giờ quy đổi và file minh chứng.
5. Chọn **Duyệt** nếu hợp lệ hoặc **Từ chối** nếu cần bổ sung.

### 4.2 Duyệt NCKH

1. Mở **Kê khai NCKH**, lọc **Đã gửi**.
2. Kiểm tra loại sản phẩm, thành viên, vai trò, tỷ lệ và tổng giờ.
3. Mở file minh chứng.
4. Chọn **Duyệt** hoặc **Từ chối**.

Tài khoản quản lý demo chỉ truy cập giảng viên Khoa Điều dưỡng và các đơn vị
con. Gọi trực tiếp URL hồ sơ ngoài phạm vi cũng bị từ chối.

### 4.3 Chuẩn bị định mức

Trước khi tính giờ, kiểm tra:

- **Đối tượng:** định mức giờ chuẩn và NCKH nền.
- **Chức danh:** tỷ lệ định mức và tỷ lệ giờ đứng lớp tối thiểu.
- **Bộ môn:** đơn vị gom giảng viên để tính vượt định mức.
- **Giảm trừ định mức:** thời gian nghỉ hoặc nhiệm vụ đặc biệt.
- **Quy đổi giờ chuẩn:** chuyển NCKH sang HĐ chuyên môn hoặc ngược lại.

Demo Nguyễn Văn A đã được gắn đầy đủ các mục trên.

### 4.4 Tính giờ

1. Mở **Tính giờ chuẩn**.
2. Chọn năm `2026` và Khoa Điều dưỡng.
3. Chọn **Xem trước**. Hệ thống báo số giảng viên xử lý và hồ sơ cấu hình thiếu.
4. Nếu hợp lệ, chọn **Tính giờ**.
5. Kiểm tra kết quả chi tiết và báo cáo.
6. Chỉ chọn **Khóa** sau khi đã xử lý hết hồ sơ chờ duyệt.

Khi chưa khóa, có thể tính lại hoặc hoàn tác. Khi đã khóa, kết quả trong phạm vi
đơn vị không thể tính lại hoặc hoàn tác.

### 4.5 Vượt định mức và báo cáo

1. Mở **Vượt DM bộ môn (Đ.17)**.
2. Chọn năm, mở Bộ môn Điều dưỡng cơ bản (Demo).
3. Tính pool, nhập phần phân bổ cho thành viên và lưu.
4. Đối chiếu tổng phân bổ với pool vượt trước khi khóa.
5. Mở **Báo cáo thống kê** để lọc, in hoặc xuất Excel.

## 5. Công thức hệ thống

- **Trực tiếp giảng dạy:** số tiết lý thuyết/thực hành được phân công cho giảng viên trên lịch trong năm dương lịch. Khi đổi giảng viên trên lịch, kết quả tính lại cập nhật theo người mới.
- **Giờ HĐ chuyên môn:** tổng giờ HĐ CM đã duyệt ± giờ quy đổi.
- **Tổng giờ chuẩn:** giờ trực tiếp giảng dạy + giờ HĐ chuyên môn.
- **Định mức thực tế:** định mức đối tượng × tỷ lệ chức danh × (1 − % giảm trừ).
- **Giờ đứng lớp tối thiểu:** định mức thực tế × tỷ lệ đứng lớp tối thiểu.
- **Giờ NCKH:** phần giờ NCKH đã duyệt của giảng viên ± giờ quy đổi.
- **Đạt chung:** đạt tổng giờ, đạt giờ đứng lớp tối thiểu và đạt định mức NCKH.

Hồ sơ Nháp, Đã gửi và Từ chối không tham gia phép tính.

## 6. Kịch bản test đề xuất

1. Giảng viên mở hồ sơ HĐ CM nháp, sửa rồi gửi duyệt.
2. Quản lý đăng nhập, lọc Đã gửi và duyệt hồ sơ đó.
3. Quản lý từ chối hồ sơ NCKH đang chờ.
4. Giảng viên đăng nhập lại, sửa hồ sơ vừa bị từ chối rồi gửi lại.
5. Quản lý duyệt, chạy **Xem trước** và **Tính giờ** cho năm `2026`.
6. Giảng viên xem kết quả cá nhân và xuất Excel.
7. Quản lý xem báo cáo/pool vượt định mức; chỉ thử khóa khi đã kết thúc kịch bản.

## 7. Tạo lại riêng dữ liệu Giờ chuẩn

Không cần seed lại toàn web:

```powershell
php artisan db:seed --class="Modules\StandardHours\Database\Seeders\StandardHoursDemoSeeder"
```

Seeder có thể chạy lại an toàn: các bản ghi demo được cập nhật theo khóa cố định,
không nhân đôi sau mỗi lần chạy.
