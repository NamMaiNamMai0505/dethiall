# PROJECT_STRUCTURE.md

# Laravel Training Management System

## Project Overview

Đây là hệ thống Quản lý Đào tạo được xây dựng trên Laravel 12 theo kiến trúc Module.

Mọi chức năng mới phải tích hợp vào kiến trúc hiện có.

Không được tạo một kiến trúc mới.

---

# Root Structure

```
app/
bootstrap/
config/
database/
lang/
modules/
public/
resources/
routes/
storage/
tests/
vendor/
```

---

# Modules

Tất cả chức năng nghiệp vụ nằm trong thư mục

```
modules/
```

Mỗi module độc lập.

Ví dụ

```
modules/

Authentication/

Dashboard/

Student/

Instructor/

Class/

Subject/

TrainingSchedule/

TeachingAssignment/

Unit/

Building/

Classroom/

Role/

Permission/

StandardHours/ (module sẽ phát triển)
```

---

# Cấu trúc chuẩn của một Module

Mỗi module phải tuân theo cấu trúc sau.

```
ModuleName/

Controllers/

Models/

Services/

Repositories/

Requests/

Policies/

Exports/

Imports/

Resources/

Views/

Components/

Routes/

Database/

Migrations/

Seeders/

Lang/

Tests/
```

Không tự ý thay đổi cấu trúc này.

---

# Controllers

Đường dẫn

```
modules/*/Controllers
```

Controller phải

- kế thừa ModuleBaseController
- không chứa business logic lớn
- chỉ nhận Request
- gọi Service
- trả View hoặc JSON

Không query trực tiếp nếu đã có Service.

---

# Services

Đường dẫn

```
modules/*/Services
```

Đây là nơi xử lý toàn bộ nghiệp vụ.

Ví dụ

```
CalculateStandardHoursService

SyncTeachingHoursService

ReportService

ScientificResearchService
```

Business logic phải đặt tại đây.

---

# Repository

Nếu module có xử lý dữ liệu phức tạp.

Đặt tại

```
Repositories/
```

Repository chỉ làm việc với Database.

Không tính toán nghiệp vụ.

---

# Models

Đường dẫn

```
modules/*/Models
```

Ưu tiên dùng Model đã có.

Không tạo Model trùng.

Model phải khai báo đầy đủ

fillable

casts

relationships

scope nếu cần.

---

# Requests

Đường dẫn

```
modules/*/Requests
```

Validate toàn bộ dữ liệu tại đây.

Không validate trong Controller.

---

# Views

Đường dẫn

```
modules/*/Resources/Views
```

Dùng Blade.

Không query trong Blade.

Không viết business logic.

Chỉ hiển thị dữ liệu.

---

# Javascript

Đặt trong

```
Resources/js
```

hoặc

```
public/js
```

theo module hiện có.

Không viết javascript trực tiếp trong Blade nếu không cần.

---

# Routes

Route nằm trong

```
modules/*/Routes
```

Không sửa web.php nếu module đã có route riêng.

Tên route theo chuẩn

```
module.action
```

Ví dụ

```
students.index

students.create

students.store

students.edit

students.update

students.destroy
```

---

# Database

Migration

```
database/migrations
```

hoặc

```
modules/*/Database/Migrations
```

theo module.

Seeder

```
Database/Seeders
```

---

# Permission

Permission khai báo tại

```
SyncPermissionsAndRoles.php
```

Không khai báo ở nơi khác.

Sau khi thêm permission

phải chạy

```
php artisan permissions:sync
```

---

# Authentication

Đã có.

Không tạo lại.

---

# Authorization

Đã có.

Controller kế thừa

```
ModuleBaseController
```

để tự kiểm tra permission.

Không viết lại middleware.

---

# User

Model User đã tồn tại.

Không tạo lại.

Nếu cần relationship

mở rộng model hiện có.

---

# Instructor

Đây là nguồn dữ liệu chính của giảng viên.

Không tạo bảng giáo viên mới.

---

# Student

Đây là nguồn dữ liệu học viên.

---

# Unit

Nguồn dữ liệu đơn vị.

---

# Subject

Nguồn dữ liệu môn học.

---

# Class

Nguồn dữ liệu lớp.

---

# TrainingSchedule

Nguồn dữ liệu lịch đào tạo.

Không nhập lại.

---

# TeachingAssignment

Nguồn dữ liệu phân công giảng dạy.

Không nhập lại.

---

# Module StandardHours

Module mới phải tận dụng:

Instructor

TrainingSchedule

TeachingAssignment

Unit

User

Không lưu trùng dữ liệu.

Chỉ lưu

- kê khai
- định mức
- quy đổi
- kết quả tính toán

---

# Coding Convention

Tuân thủ PSR-12.

Không hardcode.

Không duplicate.

Không query trong Blade.

Không viết business logic trong Controller.

Ưu tiên Service.

Ưu tiên Eloquent.

Có Transaction.

Có eager loading.

Có FormRequest.

Có Permission.

Có Logging nếu nghiệp vụ quan trọng.

---

# Naming Convention

Controller

```
StudentController
```

Service

```
StudentService
```

Repository

```
StudentRepository
```

Request

```
StoreStudentRequest

UpdateStudentRequest
```

Model

```
Student
```

Migration

```
create_students_table
```

---

# Development Workflow

AI phải làm đúng thứ tự

1.

Đọc AI_CONTEXT.md

↓

2.

Đọc PROJECT_STRUCTURE.md

↓

3.

Phân tích yêu cầu

↓

4.

Tìm module liên quan

↓

5.

Kiểm tra Model

↓

6.

Kiểm tra Route

↓

7.

Kiểm tra Migration

↓

8.

Kiểm tra Permission

↓

9.

Đề xuất giải pháp

↓

10.

Chờ xác nhận

↓

11.

Viết Migration

↓

12.

Viết Model

↓

13.

Viết Service

↓

14.

Viết Request

↓

15.

Viết Controller

↓

16.

Viết Blade

↓

17.

Viết Javascript

↓

18.

Viết Test

---

# Quy tắc khi sửa code

Không sửa file ngoài phạm vi yêu cầu.

Không đổi tên bảng.

Không đổi tên Route.

Không đổi tên Permission.

Không đổi API.

Không phá backward compatibility.

Nếu cần thay đổi Database

phải giải thích trước.

---

# Checklist trước khi hoàn thành

AI phải tự kiểm tra:

☐ Đã đọc AI_CONTEXT.md

☐ Đã đọc PROJECT_STRUCTURE.md

☐ Đúng PSR-12

☐ Không hardcode

☐ Có FormRequest

☐ Có Service

☐ Có Permission

☐ Có Route

☐ Có Migration

☐ Có Relationship

☐ Không duplicate

☐ Không ảnh hưởng module khác

☐ Có thể rollback migration

☐ Không tạo dữ liệu trùng

Nếu còn mục nào chưa đạt thì không được kết thúc nhiệm vụ.