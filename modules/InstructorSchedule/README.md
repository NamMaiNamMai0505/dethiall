# InstructorSchedule Module

Module quản lý lịch giảng dạy cho Giảng viên.

## Tính năng

- ✅ Xem lịch giảng dạy cá nhân của giảng viên
- ✅ Lọc theo khoảng thời gian (từ ngày - đến ngày)
- ✅ Điều hướng tuần (tuần trước/tuần sau)
- ✅ Xuất Excel lịch giảng dạy
- ✅ Thống kê tổng số tiết, phân loại theo loại tiết
- ✅ Giao diện thân thiện với badges màu sắc

## Cấu trúc Module

```
InstructorSchedule/
├── Controllers/
│   └── InstructorScheduleController.php    # Controller xử lý logic
├── Services/
│   └── InstructorScheduleService.php       # Business logic
├── Exports/
│   └── InstructorScheduleExport.php        # Excel export logic
├── Views/
│   └── index.blade.php                     # Giao diện hiển thị lịch
├── Routes/
│   └── web.php                             # Route definitions
├── Providers/
│   └── InstructorScheduleServiceProvider.php
└── README.md                               # File này
```

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | /instructor-schedule | instructor-schedule.index | Hiển thị lịch giảng dạy |
| POST | /instructor-schedule/export | instructor-schedule.export | Xuất Excel lịch giảng dạy |

## Permissions

Module sử dụng permission:

- `instructor-schedule.index` - Xem và xuất lịch giảng dạy (instructor role)

## Chi tiết tính năng

### 1. Xem lịch giảng dạy

Hiển thị lịch giảng dạy theo tuần hoặc khoảng thời gian tùy chỉnh:

- **Hiển thị theo tuần (mặc định):**
  - Thứ 2 - Chủ nhật
  - Nút điều hướng tuần trước/tuần sau

- **Hiển thị theo khoảng thời gian:**
  - Chọn "Từ ngày" và "Đến ngày"
  - Click nút "Lọc"
  - Hiển thị tất cả các ngày trong khoảng đã chọn

- **Thông tin hiển thị mỗi tiết:**
  - Môn học
  - Loại tiết (Lý thuyết/Thực hành/Tự học/Thi)
  - Lớp học
  - Phòng học
  - Tòa nhà

### 2. Xuất Excel

**Cách sử dụng:**
1. Chọn "Từ ngày" và "Đến ngày" trong form xuất Excel
2. Click nút "Xuất Excel"
3. File sẽ được tải về với tên: `lich_giang_day_YYYYMMDD_HHMMSS.xlsx`

**Thông tin trong file Excel:**

- **Tiêu đề:** "LỊCH GIẢNG DẠY"
- **Header:**
  - Dòng 1: Tiêu đề (font size 16, bold, merge toàn bộ cột)
  - Dòng 2: Trống
  - Dòng 3: "Giảng viên: [Tên giảng viên]"
  - Dòng 4: "Từ ngày [DD/MM/YYYY] đến ngày [DD/MM/YYYY]"
  - Dòng 5: Trống
  - Dòng 6: Header bảng (Ngày, Tên lớp, Tiết 1-9)

- **Cấu trúc bảng:**
  - Cột A: Ngày (dd/mm/yyyy - Thứ)
  - Cột B: Tên lớp
  - Cột C-K: Tiết 1-9

**Định dạng nội dung tiết học:**
```
Tên môn học - Loại tiết
Lớp: Mã lớp
Phòng: Tên phòng
```

**Loại tiết học (viết tắt):**
- `LT` - Lý thuyết
- `TH` - Thực hành
- `Ôn` - Tự học
- `Thi/KT` - Thi/Kiểm tra

**Đặc điểm:**
- ✅ Xuất tất cả các ngày trong khoảng thời gian, kể cả ngày không có lịch
- ✅ Ngày không có lịch: tất cả 9 tiết hiển thị "Trống lịch"
- ✅ Merge cell cột "Ngày" khi giảng viên dạy nhiều lớp trong cùng một ngày
- ✅ Tự động wrap text và căn giữa nội dung
- ✅ Border cho toàn bộ bảng
- ✅ Header có background màu xám

**Độ rộng cột:**
- Cột Ngày: 20
- Cột Tên lớp: 15
- Các cột Tiết: 25

### 3. Thống kê

Hiển thị thống kê cho khoảng thời gian đang xem:
- Tổng số lớp giảng dạy
- Tổng số môn học
- Số tiết lý thuyết
- Số tiết thực hành
- Số tiết tự học
- Số tiết thi/kiểm tra
- Tổng số tiết

## Service Methods

### InstructorScheduleService

**Date & Time Methods:**
- `getCurrentWeekDates()` - Lấy ngày đầu/cuối tuần hiện tại
- `getWeekDates($weekOffset)` - Lấy ngày đầu/cuối tuần với offset
- `getDateRangeFromRequest($request)` - Lấy khoảng thời gian từ request
- `getDateRangeLabel($dateRange)` - Format label hiển thị khoảng thời gian
- `getWeekRangeLabel($weekDates)` - Format label hiển thị tuần

**Calendar & Display Methods:**
- `buildWeeklyCalendar($schedules, $dateRange)` - Xây dựng cấu trúc lịch để hiển thị
- `calculateWeekStatistics($schedules)` - Tính thống kê cho khoảng thời gian

**Export Methods:**
- `getScheduleDetailsForExport($instructorId, $startDate, $endDate)` - Lấy dữ liệu để export Excel
- `getLessonTypeAbbreviation($lessonType)` - Chuyển loại tiết sang viết tắt (private)

**UI Helper Methods:**
- `getLessonTypeLabel($lessonType)` - Lấy label loại tiết (tiếng Việt đầy đủ)
- `getLessonTypeBadgeClass($lessonType)` - Lấy CSS class cho badge loại tiết

## Export Class

### InstructorScheduleExport

Class xử lý export Excel, implement các interface:

- `FromArray` - Dữ liệu từ array
- `WithColumnWidths` - Định nghĩa độ rộng cột
- `WithEvents` - Xử lý sự kiện format (merge cells, borders, etc.)
- `WithStyles` - Định nghĩa style cho header
- `WithTitle` - Tiêu đề sheet Excel

**Properties:**
- `$scheduleData` - Collection dữ liệu lịch giảng dạy
- `$startDate` - Ngày bắt đầu
- `$endDate` - Ngày kết thúc
- `$instructor` - Thông tin giảng viên

## Technical Notes

### Query Performance

- Sử dụng eager loading để tránh N+1 queries:
  ```php
  ->with(['subject', 'classroom.building', 'trainingSchedule.classModel'])
  ```

### Date Range Priority

Hệ thống ưu tiên xử lý date range theo thứ tự:
1. **Custom range** (nếu có `date_from` và `date_to` trong request)
2. **Week-based navigation** (nếu không có custom range, dùng `week_offset`)

Code logic:
```php
if ($request->filled('date_from') && $request->filled('date_to')) {
    // Use custom range
} else {
    // Use week navigation
}
```

## Bảo mật

- **Authentication**: Chỉ user đã đăng nhập mới truy cập được
- **Authorization**:
  - Kiểm tra `auth()->user()->isInstructor()` trong controller
  - Yêu cầu permission `instructor-schedule.index`
- **Data Isolation**: Mỗi giảng viên chỉ xem/xuất được lịch của chính mình
  - Filter theo `instructor_id` từ `auth()->user()->instructor_id`

## Dependencies

Module này sử dụng:

- **Laravel packages:**
  - `maatwebsite/excel` - Export Excel
  - `spatie/laravel-permission` - RBAC
- **Tables:**
  - `schedule_details`
  - `instructors`
  - `users`
  - `subjects`
  - `classrooms`
  - `buildings`
  - `training_schedules`
  - `classes`
- **Frontend:**
  - Tailwind CSS v4
  - Alpine.js (nếu có)

## Support

Liên hệ team development nếu có vấn đề.
