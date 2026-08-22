# BUSINESS_RULES.md

# BUSINESS RULES

Module: Quản lý Giờ Chuẩn Giảng Viên

Version: 1.0

---

# Mục tiêu

Module dùng để quản lý và tính toán:

- Giờ giảng dạy
- Giờ quy đổi hoạt động chuyên môn
- Giờ nghiên cứu khoa học
- Định mức giờ chuẩn
- Định mức nghiên cứu khoa học
- Báo cáo tổng hợp

Module phải tích hợp với hệ thống Quản lý Đào tạo hiện có.

Không nhập lại dữ liệu đã tồn tại.

---

# Nguồn dữ liệu

Thông tin giảng viên

→ Module Instructor

Thông tin đơn vị

→ Module Unit

Thông tin lịch giảng

→ Module TrainingSchedule

Thông tin phân công giảng dạy

→ Module TeachingAssignment

Thông tin người dùng

→ User

Không được tạo dữ liệu trùng.

---

# Phân loại dữ liệu

Hệ thống có ba nhóm dữ liệu.

## 1. Giờ giảng

Lấy tự động từ hệ thống.

Không nhập tay.

Nguồn:

TrainingSchedule

TeachingAssignment

---

## 2. Giờ quy đổi

Giảng viên kê khai.

Admin được sửa.

Lưu các trường:

- Danh mục
- Tên hoạt động
- Ngày thực hiện
- Số lượng
- Hệ số quy đổi hoặc số giờ quy đổi
- Ghi chú

---

## 3. Nghiên cứu khoa học

Giảng viên kê khai.

Admin duyệt.

Lưu:

- Danh mục
- Tên sản phẩm
- Vai trò
- Chủ nhiệm hay thành viên
- Ngày công bố
- Ngày nghiệm thu
- Số thành viên
- Tổng giờ quy đổi

---

# Định mức giờ chuẩn

Định mức phụ thuộc:

- Đối tượng trường
- Chức danh

Ví dụ

Đối tượng 01

280 giờ

Đối tượng 02

380 giờ

Đối tượng 03

430 giờ

Không hardcode.

Lấy từ Database.

---

# Định mức giờ hành chính theo đối tượng

Danh mục Đối tượng lưu riêng định mức giờ hành chính:

- Đối tượng 01: 840 giờ.
- Đối tượng 02: 1140 giờ.
- Đối tượng 03: 1290 giờ.

Giá trị được lưu tại `standard_object_types.administrative_hours`, hiển thị
cùng định mức giờ chuẩn và NCKH, đồng thời cho phép quản trị viên cập nhật từ
form Đối tượng. Không suy diễn hoặc hardcode giá trị này tại giao diện.

---

# Tỷ lệ chức danh

Ví dụ

Hiệu trưởng

10%

Phó Hiệu trưởng

15%

Trưởng phòng

20%

Chủ nhiệm khoa / Trưởng khoa

60%

Chủ nhiệm khoa / Trưởng khoa kiêm Bí thư Đảng ủy, Bí thư chi bộ

45%

Chủ nhiệm khoa / Trưởng khoa kiêm Phó Bí thư Đảng ủy, Phó Bí thư chi bộ

50%

Phó Chủ nhiệm khoa

70%

Trưởng bộ môn

80%

Giảng viên

100%

Không hardcode.

Lấy từ bảng chức danh.

Nếu giảng viên kiêm nhiệm nhiều chức vụ thì áp dụng chức danh cao nhất theo
Thông tư. Chức danh và biến thể kiêm nhiệm phải là các bản ghi riêng trong
danh mục để có thể kiểm tra, phê duyệt và truy vết.

---

# Kỳ tính riêng của module Giờ chuẩn GV

Module Giờ chuẩn GV hỗ trợ hai chế độ:

- Năm dương lịch: từ 01/01 đến 31/12.
- Năm học: lấy ngày bắt đầu và kết thúc từ danh mục `academic_years` dùng chung.

Chế độ được lưu trong `standard_hours_settings` và chỉ tác động đến module
StandardHours. Dashboard, LMS và Quản lý điểm tiếp tục sử dụng năm học theo
quy tắc riêng của các module đó.

Mỗi dữ liệu phát sinh phải lưu `period_mode` để khi đổi công tắc không diễn
giải sai dữ liệu lịch sử.

---

# Mức tối thiểu đứng lớp

Theo quy định.

Mỗi chức danh phải đạt tối thiểu:

50%

giờ trực tiếp giảng dạy.

Ví dụ

Định mức

380

↓

Tối thiểu đứng lớp

190

Nếu nhỏ hơn

=> Không đạt.

---

# Định mức nghiên cứu khoa học

Theo đối tượng.

Đại học

600 giờ

Cao đẳng

300 giờ

Trung cấp

150 giờ

Không hardcode.

---

# Quy đổi hoạt động chuyên môn

Mỗi hoạt động gồm:

- Danh mục
- Hệ số quy đổi
hoặc
- Số giờ cố định

Nếu dùng hệ số

Giờ

=

Số lượng

×

Hệ số

Nếu dùng số giờ cố định

Giờ

=

Số lượng

×

Số giờ

Ví dụ

20 tiết

×

1.2

=

24 giờ

Giờ giảng lấy từ Lịch huấn luyện phải áp dụng hệ số của danh mục hoạt động
được gán cho từng tiết. Nếu chưa gán thì dùng danh mục tiết giảng ban ngày tại
giảng đường đang được cấu hình là mặc định; không viết hệ số trực tiếp trong
CalculationService.

Giờ giảng dạy khác do giảng viên kê khai chỉ được cộng sau khi cấp quản lý
phê duyệt hồ sơ kê khai giờ chuẩn.

---

# Quy đổi nghiên cứu khoa học

Giờ quy đổi lấy theo danh mục.

Ví dụ

Đề tài cấp cơ sở

1200

Đề tài cấp Bộ

2400

Đề tài cấp Quốc gia

3600

Giáo trình

1200

Bài báo ISSN

300

...

Danh mục lưu trong Database.

Không hardcode.

---

# Chia giờ khi có nhiều thành viên

Nếu có

1 người

100%

Nếu có

2 người

Chủ nhiệm

2/3

Thành viên

1/3

Nếu có

3 người

Chủ nhiệm

1/2

Phần còn lại chia theo đóng góp.

Nếu trên

3 người

Chủ nhiệm

1/3

Phần còn lại chia theo đóng góp.

Nếu không xác định được đóng góp

chia đều.

---

# Đề tài nhiều năm

Nếu đề tài kéo dài nhiều năm.

Giờ từng năm

=

Tổng giờ

/

Số năm

Ví dụ

2400 giờ

4 năm

↓

600 giờ/năm

---

# Công thức

Giờ chuẩn

=

Giờ giảng

+

Giờ quy đổi

-----------------------

Giờ NCKH

=

Tổng giờ quy đổi NCKH

-----------------------

So sánh giờ chuẩn

=

Giờ chuẩn

-

Định mức

-----------------------

So sánh NCKH

=

Giờ NCKH

-

Định mức NCKH

---

# Điều kiện đạt

Nếu

Giờ chuẩn

>=

Định mức

↓

Đạt

Nếu

<

↓

Không đạt

---

Nếu

Giờ đứng lớp

<

50%

↓

Không đạt giờ chuẩn đứng lớp

---

Nếu

Giờ NCKH

<

Định mức

↓

Không đạt NCKH

---

# Báo cáo

Báo cáo phải có

- Họ tên
- Đơn vị
- Chức danh
- Giờ giảng
- Giờ quy đổi
- Tổng giờ chuẩn
- Định mức
- Chênh lệch
- Giờ NCKH
- Định mức NCKH
- Chênh lệch
- Kết quả

Có bộ lọc

- Kỳ tính (Năm hoặc Năm học theo cài đặt riêng của Giờ chuẩn GV)
- Khoảng thời gian
- Đơn vị
- Giảng viên

Có

- Excel
- PDF
- Print

---

# Quy tắc bắt buộc

Không hardcode:

- Định mức
- Hệ số
- Tỷ lệ
- Chức danh
- Đối tượng
- Danh mục
- Quy đổi

Tất cả phải lấy từ Database.

Admin có thể thay đổi mà không cần sửa code.

Việc xét bù giờ NCKH và giờ hoạt động chuyên môn chỉ do tài khoản quản lý có
thẩm quyền thực hiện, phải có ghi chú căn cứ và không cho phép bù vượt phần
định mức còn thiếu.

Khi quyết định bù giờ, áp dụng quan hệ của Thông tư: `1 giờ chuẩn = 3 giờ
hành chính NCKH`; vì vậy NCKH sang HĐ chuyên môn dùng hệ số `1/3`, chiều
ngược lại dùng hệ số `3`.

Hoạt động mời giảng ngoài nhà trường không cộng giờ chuẩn. Hoạt động đã có
chế độ thù lao riêng vẫn được theo dõi trong kết quả chuyên môn nhưng không
đưa vào quỹ vượt định mức để tránh tính trùng.

---

# Danh mục ngành đào tạo chính thức

Nguồn chuẩn: file `DANH MỤC NGÀNH ĐÀO TẠO.docx` do nhà trường cập nhật ngày
01/08/2026.

`Mã số` (ví dụ `B.6720101`, `A.5810208`) là **khóa chính** của danh mục ngành:
mỗi chương trình đào tạo có đúng một Mã số, phân biệt được cả hệ Dân sự/Quân sự
lẫn hình thức đào tạo.

Mã số là khóa dùng cho hệ thống, **không phải mã nghiệp vụ**:

- Trên **màn hình quản trị danh mục ngành**, Mã số đứng ở cột ngoài cùng bên trái
  vì đó là khóa để tra cứu và đối chiếu bản ghi.
- Trên **báo cáo, văn bản và dữ liệu xuất ra ngoài**, Mã số **không được xuất
  hiện**. Chỉ dùng `Mã ngành`.
- Không gọi Mã số là "mã nội bộ" trên giao diện — nhãn đúng là `Mã số`.

`Mã ngành` (ví dụ `6720101`) là mã nghiệp vụ theo danh mục cấp trên, **có thể
lặp lại** giữa các chương trình khác hệ hoặc khác hình thức đào tạo — do đó
không dùng làm khóa. Đây là mã duy nhất được nêu trên văn bản, báo cáo và mọi
dữ liệu xuất ra ngoài.

Thứ tự hiển thị trên màn hình danh mục ngành: `Mã số` → `Mã ngành` → `Tên ngành`
→ `Trình độ` → `Thời gian` → `Hệ` → `Hình thức`.

| Mã số | Mã ngành | Tên ngành | Trình độ | Thời gian | Hệ | Hình thức |
|---|---|---|---|---:|---|---|
| B.6720101 | 6720101 | Y sỹ đa khoa | Cao đẳng | 36 tháng | Dân sự | Chính quy |
| B.6720201 | 6720201 | Dược | Cao đẳng | 36 tháng | Dân sự | Chính quy |
| B.6720301 | 6720301 | Điều dưỡng | Cao đẳng | 36 tháng | Dân sự | Chính quy |
| A.6720101 | 6720101 | Y sỹ đa khoa | Cao đẳng | 36 tháng | Quân sự | Chính quy |
| A.6720301 | 6720301 | Điều dưỡng | Cao đẳng | 36 tháng | Quân sự | Chính quy |
| A.6720302 | 6720301 | Điều dưỡng | Cao đẳng | 36 tháng | Quân sự | Liên thông |
| A.5810207 | 5810207 | Kỹ thuật chế biến món ăn | Trung cấp | 24 tháng | Quân sự | Chính quy |
| A.5810208 | 5810207 | Kỹ thuật chế biến món ăn | Trung cấp | 12 tháng | Quân sự | Chuyển loại |
| A.5720101 | 5720101 | Y sỹ đa khoa | Trung cấp | 30 tháng | Quân sự | Chính quy |
| A.5340202 | 5340202 | Tài chính – Ngân hàng | Trung cấp | 24 tháng | Quân sự | Chính quy |
| A.6720100 | 6720100 | Nhân viên quân y đại đội | Sơ cấp | 6 tháng | Quân sự | Chính quy |

Khi chuyển từ mã nội bộ cũ sang mã ngành chính thức, phải giữ nguyên liên kết
với lớp, môn học, lịch đào tạo và giảng viên; không xóa rồi tạo lại bản ghi đang
được tham chiếu.

---

# Quy tắc phát triển

Mọi thay đổi nghiệp vụ phải cập nhật file BUSINESS_RULES.md trước.

Sau đó mới sửa code.

Code luôn phải tuân thủ file này.

Nếu code khác BUSINESS_RULES.md thì BUSINESS_RULES.md là tài liệu chuẩn.
