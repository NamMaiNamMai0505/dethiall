# AI_CONTEXT.md

## Mục tiêu dự án

> Trước khi tiếp tục roadmap, đọc `docs/ROADMAP_STATUS.md` để biết trạng thái
> triển khai, PR, migration và backlog mới nhất.

Đây là hệ thống Quản lý Đào tạo được phát triển bằng Laravel 12.

Hệ thống đã hoạt động ổn định.

Mọi thay đổi phải đảm bảo:

- Không phá vỡ module cũ.
- Không thay đổi database cũ nếu không cần.
- Không sửa các API đang hoạt động.
- Luôn ưu tiên tái sử dụng module hiện có.

---

# Kiến trúc

Laravel 12

Kiến trúc Module.

Hiện có khoảng 183 Routes.

Module được đăng ký trong providers.php.

Request

↓

Route

↓

Controller

↓

Service

↓

Repository (nếu có)

↓

Model

↓

Database

Controller kế thừa ModuleBaseController.

Không được bỏ qua ModuleBaseController.

---

# Module hiện có

Authentication

Dashboard

Student

Instructor

Class

Subject

TrainingSchedule

TeachingAssignment

Unit

Building

Classroom

Role

Permission

User

Không tạo lại các module trên.

---

# Phân quyền

Permission theo chuẩn

module.action

Ví dụ

users.index

users.create

subject.edit

training-schedule.update

Permission được khai báo trong

SyncPermissionsAndRoles.php

Sau khi thêm permission phải chạy

php artisan permissions:sync

Không tạo permission ở nơi khác.

---

# Coding Standard

Bắt buộc

PSR-12.

Không hardcode.

Không duplicate.

Không query trong Blade.

Không business logic lớn trong Controller.

Ưu tiên Service.

Validate bằng FormRequest.

Model dùng Eloquent.

Ưu tiên Relationship.

Không dùng DB::table nếu đã có Model.

Transaction khi ghi nhiều bảng.

Eager Loading để tránh N+1.

Không sửa module khác nếu không cần.

---

# Module đang phát triển

Module:

Quản lý Giờ Chuẩn Giảng Viên

Module này tích hợp với

Instructor

TrainingSchedule

TeachingAssignment

User

Unit

Không nhập lại dữ liệu đã có.

Chỉ lưu dữ liệu phát sinh.

---

# Nguồn dữ liệu

Thông tin giảng viên

→ Instructor

Thông tin đơn vị

→ Unit

Thông tin giờ giảng

→ TrainingSchedule

→ TeachingAssignment

Thông tin User

→ User

Không lưu trùng dữ liệu.

---

# Quy tắc tính giờ

Hệ thống có 3 nhóm:

1. Giờ giảng

2. Giờ quy đổi

3. Giờ NCKH

Giờ giảng

Lấy tự động từ TrainingSchedule.

Không nhập tay.

Giờ quy đổi

Giảng viên kê khai.

Admin được sửa.

Giờ NCKH

Giảng viên kê khai.

Admin duyệt.

---

# Quy tắc tính toán

Giờ chuẩn

=

Giờ giảng

+

Giờ quy đổi

----------------------

Giờ NCKH

=

Tổng giờ NCKH quy đổi

----------------------

So sánh

=

Thực tế

-

Định mức

Nếu >=0

Đạt

Nếu <0

Không đạt

Nếu giờ đứng lớp <50%

Không đạt giờ chuẩn đứng lớp.

---

# Quy tắc NCKH

Nếu đề tài có

2 thành viên

Chủ nhiệm

2/3

Thành viên

1/3

Nếu

3 thành viên

Chủ nhiệm

1/2

Nếu trên

3 thành viên

Chủ nhiệm

1/3

Nếu không có tỷ lệ đóng góp

Chia đều.

Nếu đề tài nhiều năm

Tổng giờ

/

Số năm

=

Giờ từng năm.

---

# Không được Hardcode

Các dữ liệu sau phải lưu trong Database.

- Định mức giờ chuẩn
- Định mức NCKH
- Hệ số quy đổi
- Danh mục hoạt động
- Danh mục NCKH
- Chức danh
- Đối tượng
- Mức tối thiểu đứng lớp

Không viết trực tiếp vào code.

---

# Flow phát triển

Luôn theo đúng thứ tự

1. Phân tích

2. Thiết kế Database

3. Migration

4. Model

5. Relationship

6. Repository

7. Service

8. Controller

9. Request

10. Blade

11. Javascript

12. Permission

13. Test

Không bỏ qua bước.

---

# Quy tắc sinh code

Không sinh toàn bộ module.

Mỗi lần chỉ làm một bước.

Sau mỗi bước phải dừng.

Giải thích trước khi viết code.

Nếu chưa chắc chắn phải hỏi.

Không được tự ý sửa code cũ.

Nếu phát hiện module tương tự thì đề xuất tái sử dụng.

---

# Quy tắc Review Code

Trước khi kết thúc mỗi nhiệm vụ AI phải tự kiểm tra:

✓ Có đúng PSR-12?

✓ Có dùng Model thay vì DB::table?

✓ Có validate?

✓ Có transaction?

✓ Có eager loading?

✓ Có permission?

✓ Có route?

✓ Có service?

✓ Có ảnh hưởng module khác?

✓ Có hardcode không?

Nếu còn lỗi phải sửa trước khi kết thúc.

---

# QUY TẮC BẮT BUỘC TRƯỚC KHI VIẾT CODE

Mỗi lần bắt đầu một nhiệm vụ AI phải:

1. Đọc AI_CONTEXT.md.

2. Kiểm tra toàn bộ module liên quan.

3. Tìm model đã tồn tại.

4. Tìm migration đã tồn tại.

5. Tìm route đã tồn tại.

6. Tìm permission đã tồn tại.

7. Tìm Blade đã tồn tại.

8. Chỉ tạo mới nếu chưa có.

9. Nếu có thể tái sử dụng thì KHÔNG tạo mới.

10. Không được đoán cấu trúc project.

Nếu chưa rõ phải hỏi người dùng trước.
