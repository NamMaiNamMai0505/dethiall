# FEATURE_SPEC.md

> Version: 1.0.0
>
> Status: Draft
>
> Module: Quản lý Giờ Chuẩn Giảng Viên
>
> Framework: Laravel 12
>
> Architecture: Modular
>
> Last Update: 2026-07-05

---

# TABLE OF CONTENTS

1. Module Overview
2. Objectives
3. Scope
4. Existing System Overview
5. Architecture Integration
6. Actors
7. User Roles
8. Functional Modules
9. Non-Functional Requirements
10. Constraints
11. Design Principles
12. Success Criteria

---

# FEA-001 MODULE OVERVIEW

## 1.1 Module Name

Quản lý Giờ Chuẩn Giảng Viên
(Standard Hours Management)

---

## 1.2 Purpose

Module này được xây dựng nhằm hỗ trợ Nhà trường quản lý toàn bộ:

- Giờ giảng dạy
- Giờ quy đổi hoạt động chuyên môn
- Giờ nghiên cứu khoa học
- Định mức giờ chuẩn
- Định mức nghiên cứu khoa học
- Báo cáo tổng hợp
- Thống kê theo đơn vị
- Thống kê theo năm học

Module phải tích hợp hoàn toàn với hệ thống Quản lý Đào tạo hiện có.

Không được xây dựng thành hệ thống độc lập.

---

## 1.3 Objectives

Các mục tiêu chính của module.

### OBJ-001

Quản lý giờ chuẩn theo đúng quy định hiện hành.

---

### OBJ-002

Không nhập lại dữ liệu đã tồn tại.

---

### OBJ-003

Tái sử dụng dữ liệu của các module hiện có.

---

### OBJ-004

Giảm tối đa thao tác nhập liệu.

---

### OBJ-005

Toàn bộ quy tắc tính toán phải được cấu hình từ Database.

Không được Hardcode.

---

### OBJ-006

Cho phép thay đổi định mức mà không cần sửa source code.

---

### OBJ-007

Có khả năng mở rộng trong tương lai.

---

### OBJ-008

Đảm bảo tính chính xác của báo cáo.

---

# FEA-002 SCOPE

## 2.1 In Scope

Module bao gồm các chức năng sau.

### Quản lý danh mục

- Đối tượng
- Chức danh
- Định mức giờ chuẩn
- Định mức NCKH
- Danh mục hoạt động chuyên môn
- Danh mục NCKH

---

### Quản lý kê khai

- Kê khai hoạt động chuyên môn
- Kê khai NCKH

---

### Đồng bộ

- Đồng bộ giờ giảng
- Đồng bộ giảng viên
- Đồng bộ đơn vị

---

### Quản lý

- Chỉnh sửa kê khai
- Duyệt kê khai
- Khóa dữ liệu

---

### Báo cáo

- Báo cáo cá nhân
- Báo cáo đơn vị
- Báo cáo toàn trường

---

### Dashboard

- Tổng quan giờ chuẩn
- Tổng quan NCKH

---

### Export

- Excel

- PDF

- Print

---

## 2.2 Out Of Scope

Module KHÔNG thực hiện các chức năng sau.

- Quản lý nhân sự.
- Quản lý tiền lương.
- Quản lý chấm công.
- Quản lý nghỉ phép.
- Quản lý hồ sơ cán bộ.
- Quản lý tài chính.

---

# FEA-003 EXISTING SYSTEM OVERVIEW

Module phải hoạt động trên hệ thống Laravel 12 hiện có.

Không được thay đổi kiến trúc.

Hiện tại hệ thống đã có:

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

Module mới phải tái sử dụng tối đa các module này.

---

# FEA-004 ARCHITECTURE INTEGRATION

## 4.1 Kiến trúc

Module được tích hợp theo kiến trúc Module.

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

---

## 4.2 Controller

Controller phải kế thừa:

ModuleBaseController

Không được bỏ qua.

---

## 4.3 Permission

Permission theo chuẩn

module.action

Ví dụ

standard-hours.index

standard-hours.create

research.index

research.report

Permission được đồng bộ bằng

SyncPermissionsAndRoles.php

---

## 4.4 View

Sử dụng Blade.

Không tạo Layout mới.

Không query trong Blade.

---

## 4.5 Database

Migration phải tuân thủ DATABASE_RULES.md.

---

# FEA-005 MODULE INTEGRATION

Module mới phải tích hợp với các module sau.

## MOD-001 Instructor

Nguồn dữ liệu giảng viên.

Không tạo bảng giáo viên mới.

---

## MOD-002 User

Nguồn tài khoản.

Không tạo User mới.

---

## MOD-003 Unit

Nguồn đơn vị.

Không lưu trùng.

---

## MOD-004 TrainingSchedule

Nguồn giờ giảng.

Không nhập tay.

---

## MOD-005 TeachingAssignment

Nguồn phân công.

Sử dụng để tính giờ.

---

## MOD-006 Subject

Nguồn môn học.

---

## MOD-007 Class

Nguồn lớp.

---

# FEA-006 ACTORS

Hệ thống có các Actor sau.

## ACT-001 Super Admin

Toàn quyền.

---

## ACT-002 Admin

Quản trị module.

---

## ACT-003 Instructor

Giảng viên.

Có quyền:

- kê khai
- xem báo cáo
- cập nhật dữ liệu được phép

---

## ACT-004 Department Manager (Future)

Trưởng khoa.

Có quyền duyệt.

(Chưa triển khai)

---

# FEA-007 USER ROLES

Role hiện có.

super-admin

student

instructor

Module phải sử dụng Role hiện có.

Không tạo Role mới nếu không cần.

---

# FEA-008 FUNCTIONAL MODULES

Module chia thành các phân hệ.

FM-001

Danh mục

---

FM-002

Định mức

---

FM-003

Kê khai hoạt động chuyên môn

---

FM-004

Kê khai NCKH

---

FM-005

Đồng bộ giờ giảng

---

FM-006

Tính giờ chuẩn

---

FM-007

Báo cáo

---

FM-008

Dashboard

---

# FEA-009 NON-FUNCTIONAL REQUIREMENTS

## Performance

Hệ thống phải hỗ trợ ít nhất:

- 100 người dùng đồng thời.
- Báo cáo dưới 5 giây.

---

## Security

Bắt buộc kiểm tra Permission.

Không bỏ qua Authentication.

---

## Maintainability

Không Hardcode.

Tất cả quy tắc lấy từ Database.

---

## Scalability

Có thể mở rộng thêm:

- KPI
- Thi đua
- Tiền thưởng
- API

---

# FEA-010 CONSTRAINTS

Bắt buộc.

Không được:

- sửa module cũ nếu không cần.
- tạo dữ liệu trùng.
- Hardcode hệ số.
- Hardcode định mức.
- Hardcode chức danh.
- Hardcode đối tượng.

---

# FEA-011 DESIGN PRINCIPLES

Module phải tuân thủ.

- SOLID
- DRY
- KISS
- Laravel Best Practice
- PSR-12

Business Logic phải nằm trong Service.

Không viết Business Logic lớn trong Controller.

---

# FEA-012 SUCCESS CRITERIA

Module được coi là hoàn thành khi:

✓ Tính đúng giờ chuẩn.

✓ Tính đúng NCKH.

✓ Không có dữ liệu trùng.

✓ Báo cáo chính xác.

✓ Không ảnh hưởng module cũ.

✓ Có Permission.

✓ Có Validation.

✓ Có Export.

✓ Có Dashboard.

✓ Tuân thủ BUSINESS_RULES.md.

✓ Tuân thủ AI_CONTEXT.md.

✓ Tuân thủ PROJECT_STRUCTURE.md.

---

# BUSINESS USE CASES & WORKFLOW
# =====================================================

# FEA-013 BUSINESS WORKFLOW

Module Quản lý Giờ Chuẩn được chia thành 7 quy trình nghiệp vụ chính.

WF-001
Khởi tạo danh mục

↓

WF-002
Đồng bộ dữ liệu

↓

WF-003
Giảng viên kê khai

↓

WF-004
Kiểm tra dữ liệu

↓

WF-005
Tính giờ chuẩn

↓

WF-006
Sinh báo cáo

↓

WF-007
Khóa dữ liệu năm học

---

# WORKFLOW OVERVIEW

```text
Khởi tạo danh mục

↓

Đồng bộ dữ liệu từ hệ thống

↓

Giảng viên kê khai

↓

Admin kiểm tra

↓

Hệ thống tính toán

↓

Sinh báo cáo

↓

Khóa dữ liệu
```

---

# UC-001
Quản lý Đối tượng

## Actor

Admin

## Mục đích

Quản lý các đối tượng tính giờ chuẩn.

Ví dụ

- Đối tượng 01
- Đối tượng 02
- Đối tượng 03

## Tiền điều kiện

Admin đã đăng nhập.

Có quyền

standard-hours.object.manage

## Luồng chính

1.

Mở màn hình.

2.

Xem danh sách.

3.

Thêm mới.

4.

Sửa.

5.

Ngừng sử dụng.

## Kết quả

Danh mục được lưu.

Không được xóa nếu đang sử dụng.

---

# UC-002

Quản lý Chức danh

Actor

Admin

Cho phép

- thêm

- sửa

- khóa

Không được xóa nếu đã phát sinh dữ liệu.

---

# UC-003

Quản lý Định mức giờ chuẩn

Actor

Admin

Mục tiêu

Cho phép cấu hình

Định mức

Theo

- Đối tượng

- Chức danh

Không hardcode.

---

# UC-004

Quản lý Định mức NCKH

Actor

Admin

Cho phép

- thêm

- sửa

- khóa

Không hardcode.

---

# UC-005

Quản lý Danh mục Hoạt động chuyên môn

Actor

Admin

Mỗi hoạt động gồm

- Mã

- Tên

- Hệ số

hoặc

- Số giờ

Không nhập cả hai.

---

# UC-006

Quản lý Danh mục NCKH

Actor

Admin

Ví dụ

- Đề tài

- Giáo trình

- Sách

- Bài báo

- Hội thảo

...

---

# UC-007

Đồng bộ Giảng viên

Actor

Admin

Nguồn

Instructor

## Mục tiêu

Không tạo dữ liệu mới.

Chỉ đọc.

Đồng bộ khi cần.

---

# UC-008

Đồng bộ Giờ giảng

Actor

Admin

Nguồn

TrainingSchedule

TeachingAssignment

## Kết quả

Sinh bảng tổng hợp giờ giảng.

Không cho sửa tay.

---

# UC-009

Kê khai Hoạt động chuyên môn

Actor

Instructor

Cho phép

- thêm

- sửa

- xem

Không được

Duyệt.

---

# UC-010

Kê khai Nghiên cứu khoa học

Actor

Instructor

Cho phép

- thêm

- sửa

- tải minh chứng

- xem

---

# UC-011

Duyệt kê khai

Actor

Admin

Có thể

- duyệt

- từ chối

- trả lại

---

# UC-012

Khóa dữ liệu

Actor

Admin

Sau khi khóa

Không ai được sửa.

---

# UC-013

Tính Giờ Chuẩn

Actor

System

Input

Giờ giảng

+

Giờ quy đổi

Output

Tổng giờ chuẩn.

---

# UC-014

Tính NCKH

Actor

System

Input

Kê khai

Output

Tổng giờ NCKH.

---

# UC-015

Đánh giá Đạt

Actor

System

Nếu

Tổng giờ

>=

Định mức

↓

Đạt

Ngược lại

↓

Không đạt.

---

# UC-016

Kiểm tra 50% giờ đứng lớp

Actor

System

Nếu

Giờ giảng

<

50%

↓

Không đạt.

---

# UC-017

Dashboard

Actor

Admin

Hiển thị

- Tổng GV

- Đạt

- Không đạt

- Đạt NCKH

- Không đạt NCKH

---

# UC-018

Báo cáo

Actor

Admin

Các báo cáo

- Theo khoa

- Theo đơn vị

- Theo giảng viên

- Theo năm học

---

# UC-019

Xuất Excel

Actor

Admin

Export

xlsx

---

# UC-020

Xuất PDF

Actor

Admin

Export

pdf

---

# FEA-014 BUSINESS FLOW

## FLOW-001

Khởi tạo năm học

↓

Khai báo định mức

↓

Đồng bộ giảng viên

↓

Đồng bộ lịch giảng

↓

Giảng viên kê khai

↓

Admin kiểm tra

↓

Hệ thống tính toán

↓

Sinh báo cáo

↓

Khóa dữ liệu

---

# FEA-015 DATA FLOW

Instructor

↓

TeachingAssignment

↓

TrainingSchedule

↓

StandardHours

↓

ResearchHours

↓

Calculation Engine

↓

Reports

---

# FEA-016 MODULE DEPENDENCY

Instructor

↓

TeachingAssignment

↓

TrainingSchedule

↓

StandardHours

↓

Research

↓

Reports

---

# FEA-017 BUSINESS STATES

Draft

↓

Submitted

↓

Reviewed

↓

Approved

↓

Locked

---

# FEA-018 APPROVAL FLOW

Instructor

↓

Submit

↓

Admin Review

↓

Approved

↓

Calculation

↓

Report

---

# FEA-019 ERROR SCENARIOS

ERR-001

Không có giảng viên

↓

Không đồng bộ.

---

ERR-002

Không có lịch giảng

↓

Không tính giờ.

---

ERR-003

Thiếu định mức

↓

Không cho tính.

---

ERR-004

Thiếu danh mục

↓

Không cho kê khai.

---

ERR-005

Dữ liệu bị khóa

↓

Không cho sửa.

---

# FEA-020 BUSINESS PRIORITY

P1

Đồng bộ dữ liệu

P1

Tính giờ

P1

Báo cáo

P2

Dashboard

P2

Export

P3

API

P3

Notification

---
# TECHNICAL SPECIFICATION
# =====================================================

# FEA-021 DATABASE MAPPING

## Mục tiêu

Module Standard Hours không được tạo dữ liệu trùng với hệ thống hiện có.

Ưu tiên sử dụng dữ liệu từ các module đã tồn tại.

---

| Module | Nguồn dữ liệu | Chế độ |
|---------|---------------|---------|
| Instructor | Giảng viên | Read Only |
| User | Người dùng | Read Only |
| Unit | Đơn vị | Read Only |
| Subject | Môn học | Read Only |
| Class | Lớp | Read Only |
| TrainingSchedule | Giờ giảng | Read Only |
| TeachingAssignment | Phân công | Read Only |

---

Các bảng mới chỉ lưu dữ liệu phát sinh.

Không sao chép dữ liệu từ các bảng trên.

---

# DB-001 DATABASE DESIGN PRINCIPLES

Toàn bộ database phải tuân thủ:

- Chuẩn hóa dữ liệu (3NF)
- Không lưu dữ liệu dư thừa
- Có khóa ngoại
- Có index
- Có Soft Delete nếu cần
- Có created_at
- Có updated_at

---

# DB-002 NEW TABLES

Module sẽ tạo các bảng sau.

## standard_object_types

Danh mục đối tượng.

---

## standard_positions

Danh mục chức danh.

---

## standard_hour_norms

Định mức giờ chuẩn.

---

## research_hour_norms

Định mức NCKH.

---

## conversion_categories

Danh mục hoạt động chuyên môn.

---

## instructor_conversion_records

Kê khai hoạt động chuyên môn.

---

## research_categories

Danh mục NCKH.

---

## instructor_research_records

Kê khai NCKH.

---

## yearly_standard_results

Kết quả tính giờ.

---

# DB-003 RELATIONSHIP

Instructor

1

↓

N

Conversion Record

---

Instructor

1

↓

N

Research Record

---

Object Type

1

↓

N

Hour Norm

---

Position

1

↓

N

Hour Norm

---

Research Category

1

↓

N

Research Record

---

# DB-004 FOREIGN KEYS

Instructor

↓

instructor_id

---

User

↓

created_by

updated_by

approved_by

---

Unit

↓

unit_id

---

ObjectType

↓

object_type_id

---

Position

↓

position_id

---

# DB-005 INDEX

Index bắt buộc.

instructor_id

year

unit_id

status

research_category_id

conversion_category_id

---

# DB-006 SOFT DELETE

Áp dụng cho

Danh mục

Kê khai

Không áp dụng

Kết quả tính toán.

---

# FEA-022 CRUD MATRIX

| Module | Create | Read | Update | Delete |
|----------|-------|------|---------|---------|
| Object Types | ✓ | ✓ | ✓ | ✓ |
| Positions | ✓ | ✓ | ✓ | ✓ |
| Hour Norms | ✓ | ✓ | ✓ | ✓ |
| Research Norms | ✓ | ✓ | ✓ | ✓ |
| Conversion Categories | ✓ | ✓ | ✓ | ✓ |
| Conversion Records | ✓ | ✓ | ✓ | ✓ |
| Research Categories | ✓ | ✓ | ✓ | ✓ |
| Research Records | ✓ | ✓ | ✓ | ✓ |
| Reports | ✗ | ✓ | ✗ | ✗ |
| Dashboard | ✗ | ✓ | ✗ | ✗ |

---

# FEA-023 PERMISSION MATRIX

| Permission | Description |
|------------|-------------|
| standard-hours.index | Xem danh sách |
| standard-hours.create | Thêm |
| standard-hours.update | Sửa |
| standard-hours.delete | Xóa |
| standard-hours.calculate | Tính giờ |
| standard-hours.report | Báo cáo |
| research.index | Xem NCKH |
| research.create | Kê khai |
| research.update | Cập nhật |
| research.approve | Duyệt |
| research.report | Báo cáo |

---

# FEA-024 VALIDATION MATRIX

## Object Type

Tên

Required

Unique

Max 255

---

## Position

Tên

Required

Unique

---

## Hour Norm

Đối tượng

Required

---

Chức danh

Required

---

Số giờ

Required

Numeric

Min 0

---

## Conversion Record

Giảng viên

Required

---

Danh mục

Required

---

Ngày

Required

Date

---

Số lượng

Required

Numeric

---

## Research Record

Tên

Required

---

Danh mục

Required

---

Ngày nghiệm thu

Required

Date

---

Tổng giờ

Numeric

Min 0

---

# FEA-025 CONTROLLER RESPONSIBILITY

Controller chỉ thực hiện:

- Nhận Request
- Validate (FormRequest)
- Gọi Service
- Trả View
- Trả JSON

Không chứa Business Logic.

---

# FEA-026 SERVICE RESPONSIBILITY

Service chịu trách nhiệm:

- Đồng bộ dữ liệu
- Tính giờ
- Quy đổi
- Nghiệp vụ
- Transaction

---

# FEA-027 REPOSITORY RESPONSIBILITY

Repository chỉ:

- Query
- Filter
- Pagination
- Report Query

Không tính toán.

---

# FEA-028 REQUEST RESPONSIBILITY

Mỗi chức năng Create/Update phải có FormRequest riêng.

Ví dụ

StoreConversionRecordRequest

UpdateConversionRecordRequest

StoreResearchRecordRequest

...

---

# FEA-029 VIEW RESPONSIBILITY

Blade chỉ:

Hiển thị dữ liệu.

Không query.

Không tính toán.

Không xử lý Business Logic.

---

# FEA-030 JAVASCRIPT RESPONSIBILITY

Javascript chỉ xử lý:

- AJAX
- Validation giao diện
- Modal
- Datatable
- Select2
- Toast
- Confirm Dialog

Không tính toán nghiệp vụ.

---

# FEA-031 REPORT ENGINE

Báo cáo phải hỗ trợ.

Filter

- Năm học
- Khoa
- Đơn vị
- Giảng viên

Export

Excel

PDF

Print

---

# FEA-032 CALCULATION ENGINE

Đầu vào

Giờ giảng

+

Hoạt động chuyên môn

+

NCKH

↓

Engine

↓

Tính toán

↓

Lưu kết quả

↓

Sinh báo cáo

Không tính trực tiếp trong Controller.

---

# FEA-033 LOGGING

Các thao tác phải log.

- Duyệt
- Khóa dữ liệu
- Tính giờ
- Đồng bộ

Lưu

User

Thời gian

IP

Action

---

# FEA-034 AUDIT

Lưu lịch sử:

Ai

Làm gì

Khi nào

Giá trị cũ

Giá trị mới

---

# FEA-035 FILE STRUCTURE

Module phải có.

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

Routes/

Database/

Tests/

Không tự ý tạo cấu trúc mới.

---
# UI DESIGN • API • SERVICE DESIGN
# =====================================================

# FEA-036 SCREEN SPECIFICATION

Module Standard Hours bao gồm các màn hình sau.

---

SCR-001

Dashboard

---

SCR-002

Quản lý Đối tượng

---

SCR-003

Quản lý Chức danh

---

SCR-004

Quản lý Định mức Giờ chuẩn

---

SCR-005

Quản lý Định mức NCKH

---

SCR-006

Danh mục Hoạt động chuyên môn

---

SCR-007

Danh mục NCKH

---

SCR-008

Đồng bộ Giờ giảng

---

SCR-009

Kê khai Hoạt động chuyên môn

---

SCR-010

Kê khai NCKH

---

SCR-011

Tính Giờ chuẩn

---

SCR-012

Báo cáo

---

SCR-013

Chi tiết Báo cáo

---

SCR-014

Lịch sử tính toán

---

# SCR-001
Dashboard

Mục tiêu

Hiển thị nhanh tình trạng hoàn thành giờ chuẩn.

Widget

- Tổng giảng viên

- Đã đạt

- Chưa đạt

- Đã đạt NCKH

- Chưa đạt NCKH

- Chưa kê khai

Biểu đồ

Theo khoa

Theo năm học

Theo đơn vị

Có Drill Down.

---

# SCR-002

Object Types

Danh sách

Toolbar

+ Thêm

Import

Export

Search

Table

STT

Tên

Mô tả

Trạng thái

Action

Action

Edit

Delete

View

---

# SCR-003

Positions

Tương tự Object Types.

---

# SCR-004

Hour Norms

Filter

Đối tượng

Chức danh

Năm học

Table

Đối tượng

Chức danh

Giờ chuẩn

Giờ tối thiểu

NCKH

Action

---

# SCR-005

Research Norms

Danh sách định mức NCKH.

---

# SCR-006

Conversion Categories

Hiển thị

Tên

Đơn vị tính

Hệ số

Số giờ

Trạng thái

---

# SCR-007

Research Categories

Danh mục

Đề tài

Giáo trình

Bài báo

Sách

Hội thảo

...

---

# SCR-008

Teaching Hours Synchronization

Button

Đồng bộ

Hiển thị

Tổng lịch

Tổng tiết

Tổng giờ

Log đồng bộ.

---

# SCR-009

Conversion Declaration

Form

Danh mục

Tên hoạt động

Ngày

Số lượng

Minh chứng

Ghi chú

Buttons

Save

Submit

Cancel

---

# SCR-010

Research Declaration

Form

Danh mục

Tên

Vai trò

Ngày

Minh chứng

Số thành viên

Tổng giờ

Buttons

Save

Submit

Cancel

---

# SCR-011

Calculation

Admin Only

Buttons

Preview

Calculate

Rollback

Lock

History

---

# SCR-012

Reports

Filter

Năm học

Đơn vị

Giảng viên

Khoa

Button

Search

Excel

PDF

Print

---

# SCR-013

Report Detail

Hiển thị

Thông tin GV

↓

Giờ giảng

↓

Giờ quy đổi

↓

NCKH

↓

Định mức

↓

Kết quả

---

# SCR-014

Calculation History

Hiển thị

Người tính

Ngày

Version

Kết quả

Log

---

# FEA-037 API SPECIFICATION

API phải theo RESTful.

---

API-001

GET

/standard-hours

Danh sách.

---

API-002

GET

/standard-hours/create

Form.

---

API-003

POST

/standard-hours

Lưu.

---

API-004

GET

/standard-hours/{id}

Chi tiết.

---

API-005

PUT

/standard-hours/{id}

Cập nhật.

---

API-006

DELETE

/standard-hours/{id}

Xóa.

---

API-007

POST

/standard-hours/calculate

Tính giờ.

---

API-008

POST

/standard-hours/synchronize

Đồng bộ.

---

API-009

GET

/standard-hours/report

Báo cáo.

---

API-010

GET

/standard-hours/export/excel

Export Excel.

---

API-011

GET

/standard-hours/export/pdf

Export PDF.

---

# RESPONSE FORMAT

Success

```
{
    "success": true,
    "message": "...",
    "data": {}
}
```

Error

```
{
    "success": false,
    "message": "...",
    "errors": {}
}
```

---

# HTTP STATUS

200

Success

201

Created

400

Bad Request

401

Unauthorized

403

Forbidden

404

Not Found

422

Validation

500

Server Error

---

# FEA-038 SERVICE DESIGN

Service bắt buộc.

Không viết Business Logic trong Controller.

---

SRV-001

ObjectTypeService

---

SRV-002

PositionService

---

SRV-003

HourNormService

---

SRV-004

ResearchNormService

---

SRV-005

ConversionCategoryService

---

SRV-006

ConversionRecordService

---

SRV-007

ResearchCategoryService

---

SRV-008

ResearchRecordService

---

SRV-009

SynchronizationService

---

SRV-010

CalculationService

---

SRV-011

ReportService

---

# Service Rules

Service chỉ làm:

Business Logic

Validation Logic

Transaction

Synchronization

Calculation

Report Generation

---

# FEA-039 COMPONENT DESIGN

Request

↓

Controller

↓

Service

↓

Repository

↓

Model

↓

Database

↓

Service

↓

Resource

↓

Response

---

# FEA-040 SEQUENCE FLOW

Đồng bộ giờ giảng

Admin

↓

Controller

↓

SynchronizationService

↓

TrainingSchedule

↓

TeachingAssignment

↓

Generate Hours

↓

Save Result

↓

Return

---

# FEA-041 CALCULATION FLOW

Teaching Hours

+

Conversion Hours

+

Research Hours

↓

CalculationService

↓

Validate

↓

Apply Business Rules

↓

Calculate

↓

Save

↓

Return

---

# FEA-042 ERROR HANDLING

Business Exception

↓

Rollback Transaction

↓

Log Error

↓

Return JSON

↓

Display Toast

---

# FEA-043 UI COMPONENTS

Sử dụng

Bootstrap

Datatable

Select2

SweetAlert2

Toast

Modal

Pagination

Không sử dụng framework UI khác nếu không được phê duyệt.

---

# FEA-044 UI RULES

Toàn bộ màn hình phải:

Responsive

Có Loading

Có Empty State

Có Confirm Dialog

Có Validation

Có Permission

Không Reload nếu không cần

Ưu tiên AJAX.

---

# FEA-045 CODING FLOW

Route

↓

Controller

↓

FormRequest

↓

Service

↓

Repository

↓

Model

↓

Database

↓

Response

↓

Blade

↓

Javascript

---
# IMPLEMENTATION ROADMAP
# FILE MAPPING
# DEFINITION OF DONE
# AI DEVELOPMENT GUIDE
# =====================================================

# FEA-046 IMPLEMENTATION ROADMAP

Module phải được phát triển theo đúng thứ tự sau.

Không được bỏ qua bước.

---

PHASE-001

Project Analysis

↓

Review AI_CONTEXT.md

↓

Review PROJECT_STRUCTURE.md

↓

Review BUSINESS_RULES.md

↓

Review FEATURE_SPEC.md

↓

Review TODO.md

---

PHASE-002

Database Design

↓

Review Database

↓

Review Existing Tables

↓

Review Existing Relationships

↓

Thiết kế bảng mới

↓

Review lần cuối

↓

Viết Migration

---

PHASE-003

Model Layer

↓

Tạo Model

↓

Relationship

↓

Scopes

↓

Accessor

↓

Mutator

↓

Factory (nếu cần)

---

PHASE-004

Business Layer

↓

Service

↓

Repository

↓

Calculation Engine

↓

Synchronization

↓

Report Engine

---

PHASE-005

Controller Layer

↓

CRUD

↓

Ajax API

↓

Import

↓

Export

↓

Approval

---

PHASE-006

UI Layer

↓

Blade

↓

Javascript

↓

Ajax

↓

Datatable

↓

Modal

↓

Validation

---

PHASE-007

Testing

↓

Manual Test

↓

Business Test

↓

Permission Test

↓

Performance Test

↓

Bug Fix

---

PHASE-008

Deployment

↓

Migration

↓

Permission Sync

↓

Cache Clear

↓

Smoke Test

---

# FEA-047 FILE MAPPING

AI phải biết mỗi chức năng sẽ nằm ở đâu.

---

FM-001

Quản lý Đối tượng

Controller

ObjectTypeController

↓

Service

ObjectTypeService

↓

Model

ObjectType

↓

View

object-types

↓

Route

standard-hours/object-types

---

FM-002

Quản lý Chức danh

PositionController

↓

PositionService

↓

Position

↓

Views

↓

Route

---

FM-003

Định mức

HourNormController

↓

HourNormService

↓

HourNorm

↓

Views

---

FM-004

NCKH

ResearchController

↓

ResearchService

↓

ResearchRecord

↓

Views

---

FM-005

Hoạt động chuyên môn

ConversionController

↓

ConversionService

↓

ConversionRecord

↓

Views

---

FM-006

Calculation

CalculationController

↓

CalculationService

↓

Calculation Engine

↓

Report

---

FM-007

Report

ReportController

↓

ReportService

↓

Export

↓

PDF

↓

Excel

---

# FEA-048 DEPENDENCY MATRIX

| Module | Phụ thuộc |
|---------|-----------|
| Object Types | Không |
| Positions | Không |
| Hour Norm | Object Types, Positions |
| Conversion Categories | Không |
| Conversion Records | Instructor |
| Research Categories | Không |
| Research Records | Instructor |
| Calculation | Hour Norm, Conversion, Research, Teaching |
| Reports | Calculation |

Không triển khai Calculation trước khi hoàn thành dữ liệu đầu vào.

---

# FEA-049 MIGRATION ORDER

Migration phải tạo theo thứ tự.

001

standard_object_types

↓

002

standard_positions

↓

003

standard_hour_norms

↓

004

research_hour_norms

↓

005

conversion_categories

↓

006

research_categories

↓

007

conversion_records

↓

008

research_records

↓

009

yearly_standard_results

Không đảo thứ tự nếu có khóa ngoại.

---

# FEA-050 SERVICE DEPENDENCY

CalculationService

↓

HourNormService

↓

ConversionService

↓

ResearchService

↓

TeachingSynchronizationService

↓

ReportService

---

# FEA-051 DEVELOPMENT RULES

Không viết code nếu chưa xác định:

- Use Case
- Business Rule
- Database
- Permission
- Validation

Nếu thiếu phải hỏi người dùng.

---

# FEA-052 AI CODING CHECKLIST

Trước khi AI sinh code phải tự kiểm tra:

☐ Đã đọc AI_CONTEXT.md

☐ Đã đọc PROJECT_STRUCTURE.md

☐ Đã đọc BUSINESS_RULES.md

☐ Đã đọc FEATURE_SPEC.md

☐ Đã đọc TODO.md

☐ Xác định đúng Module

☐ Xác định đúng Use Case

☐ Kiểm tra Controller hiện có

☐ Kiểm tra Service hiện có

☐ Kiểm tra Route hiện có

☐ Kiểm tra Permission hiện có

☐ Kiểm tra Database hiện có

☐ Không tạo Model trùng

☐ Không tạo Route trùng

☐ Không tạo Migration trùng

☐ Không tạo Permission trùng

---

# FEA-053 FILE CREATION RULES

Ưu tiên mở rộng.

Không tạo file mới nếu file hiện có đáp ứng được yêu cầu.

Chỉ tạo mới khi:

- Chưa tồn tại.

- Hoặc vi phạm nguyên tắc SOLID.

---

# FEA-054 REFACTOR RULES

Không Refactor toàn bộ module.

Chỉ Refactor trong phạm vi yêu cầu.

Không đổi:

- Route

- Permission

- API

- Database

Nếu chưa được người dùng chấp thuận.

---

# FEA-055 PERFORMANCE REQUIREMENTS

Bắt buộc:

Sử dụng

Eager Loading

↓

Pagination

↓

Database Index

↓

Caching nếu cần

↓

Chunk Processing khi xử lý dữ liệu lớn

↓

Database Transaction với nghiệp vụ nhiều bảng

Không query trong vòng lặp (N+1 Query).

---

# FEA-056 SECURITY REQUIREMENTS

Toàn bộ chức năng phải:

Authentication

↓

Authorization

↓

Permission

↓

CSRF

↓

Validation

↓

Sanitize Input

↓

Escape Output

Không bỏ qua bất kỳ bước nào.

---

# FEA-057 LOGGING REQUIREMENTS

Các nghiệp vụ sau phải ghi Log:

- Đồng bộ dữ liệu

- Tính giờ

- Duyệt

- Khóa dữ liệu

- Mở khóa

- Import

- Export

Thông tin Log:

- User

- Thời gian

- IP

- Action

- Kết quả

---

# FEA-058 DEFINITION OF DONE

Một chức năng chỉ được xem là hoàn thành khi:

✓ Database hoàn chỉnh

✓ Migration chạy thành công

✓ Seeder (nếu có)

✓ Model

✓ Relationship

✓ FormRequest

✓ Service

✓ Controller

✓ Route

✓ Blade

✓ Javascript

✓ Permission

✓ Validation

✓ Logging

✓ Export

✓ Báo cáo

✓ Kiểm thử thủ công

✓ Không phát sinh lỗi

✓ Đúng BUSINESS_RULES.md

✓ Đúng PROJECT_STRUCTURE.md

✓ Đúng AI_CONTEXT.md

---

# FEA-059 ACCEPTANCE CRITERIA

Module chỉ được nghiệm thu khi:

✓ Không có dữ liệu trùng.

✓ Không Hardcode.

✓ Không phá vỡ module hiện có.

✓ Dữ liệu tính toán chính xác.

✓ Hiệu năng đạt yêu cầu.

✓ Phân quyền chính xác.

✓ Báo cáo đúng số liệu.

✓ Có khả năng mở rộng.

---

# FEA-060 AI EXECUTION PROTOCOL

Mỗi khi nhận yêu cầu mới, AI phải thực hiện theo quy trình:

BƯỚC 1

Đọc:

- AI_CONTEXT.md
- PROJECT_STRUCTURE.md
- BUSINESS_RULES.md
- FEATURE_SPEC.md
- TODO.md

↓

BƯỚC 2

Phân tích yêu cầu.

↓

BƯỚC 3

Xác định:

- Module

- Use Case

- Business Rule

- Database

↓

BƯỚC 4

Liệt kê các file sẽ sửa.

↓

BƯỚC 5

Đánh giá tác động.

↓

BƯỚC 6

Đề xuất giải pháp.

↓

BƯỚC 7

Chờ người dùng xác nhận.

↓

BƯỚC 8

Mới bắt đầu viết code.

AI không được bỏ qua bất kỳ bước nào.

---

# FEA-061 FUTURE ENHANCEMENT

Các tính năng dự kiến trong tương lai:

- KPI Giảng viên
- Thi đua - Khen thưởng
- Đánh giá cuối năm
- Workflow phê duyệt nhiều cấp
- Dashboard BI
- Phân tích thống kê
- API cho Mobile
- Thông báo Email
- Thông báo Moodle
- Đồng bộ LMS
- Đồng bộ ERP
- AI Assistant phân tích giờ chuẩn

---
