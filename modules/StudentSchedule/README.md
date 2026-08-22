# StudentSchedule Module

Module quản lý lịch học cho Học sinh.

## Tính năng

- ✅ Xem lịch học cá nhân của học sinh
- ✅ Lọc theo khoảng thời gian (từ ngày - đến ngày)
- ✅ Điều hướng tuần (tuần trước/tuần sau)
- ✅ Xuất Excel lịch học
- ✅ Thống kê tổng số tiết, phân loại theo loại tiết
- ✅ Giao diện thân thiện với badges màu sắc

## Cấu trúc Module

```
StudentSchedule/
├── Controllers/
│   └── StudentScheduleController.php       # Controller xử lý logic
├── Services/
│   └── StudentScheduleService.php          # Business logic
├── Exports/
│   └── StudentScheduleExport.php           # Excel export logic
├── Views/
│   └── index.blade.php                     # Giao diện hiển thị lịch
├── Routes/
│   └── web.php                             # Route definitions
├── Providers/
│   └── StudentScheduleServiceProvider.php
└── README.md                               # File này
```

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | /student-schedule | student-schedule.index | Hiển thị lịch học |
| POST | /student-schedule/export | student-schedule.export | Xuất Excel lịch học |

## Permissions

Module sử dụng permission:

- `student-schedule.index` - Xem và xuất lịch học (student role)

## Chi tiết tính năng

### 1. Xem lịch học

Hiển thị lịch học theo tuần hoặc khoảng thời gian tùy chỉnh:

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
  - Giảng viên
  - Phòng học
  - Tòa nhà

### 2. Xuất Excel

**Cách sử dụng:**
1. Chọn "Từ ngày" và "Đến ngày" trong form xuất Excel
2. Click nút "Xuất Excel"
3. File sẽ được tải về với tên: `lich_hoc_YYYYMMDD_HHMMSS.xlsx`

**Thông tin trong file Excel:**

- **Tiêu đề:** "LỊCH HỌC"
- **Header:**
  - Dòng 1: Tiêu đề (font size 16, bold, merge toàn bộ cột)
  - Dòng 2: Trống
  - Dòng 3: "Lớp: [Tên lớp]"
  - Dòng 4: "Từ ngày [DD/MM/YYYY] đến ngày [DD/MM/YYYY]"
  - Dòng 5: Trống
  - Dòng 6: Header bảng (Ngày, Tiết 1-9)

- **Cấu trúc bảng:**
  - Cột A: Ngày (dd/mm/yyyy - Thứ)
  - Cột B-J: Tiết 1-9

**Định dạng nội dung tiết học:**
```
Tên môn học - Loại tiết
Giảng viên - Phòng
```

**Loại tiết học (viết tắt):**
- `LT` - Lý thuyết
- `TH` - Thực hành
- `Ôn` - Tự học
- `Thi/KT` - Thi/Kiểm tra

**Đặc điểm:**
- ✅ Xuất tất cả các ngày trong khoảng thời gian, kể cả ngày không có lịch
- ✅ Ngày không có lịch: tất cả 9 tiết hiển thị "Trống lịch"
- ✅ Tự động wrap text và căn giữa nội dung
- ✅ Border cho toàn bộ bảng
- ✅ Header có background màu xám

**Độ rộng cột:**
- Cột Ngày: 20
- Các cột Tiết: 25

### 3. Thống kê

Hiển thị thống kê cho khoảng thời gian đang xem:
- Tổng số môn học
- Số tiết lý thuyết
- Số tiết thực hành
- Số tiết tự học
- Số tiết thi/kiểm tra
- Tổng số tiết

## Service Methods

### StudentScheduleService

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
- `getScheduleDetailsForExport($trainingScheduleId, $startDate, $endDate)` - Lấy dữ liệu để export Excel
- `getLessonTypeAbbreviation($lessonType)` - Chuyển loại tiết sang viết tắt (private)

## Export Class

### StudentScheduleExport

Class xử lý export Excel, implement các interface:

- `FromArray` - Dữ liệu từ array
- `WithColumnWidths` - Định nghĩa độ rộng cột
- `WithEvents` - Xử lý sự kiện format (borders, etc.)
- `WithStyles` - Định nghĩa style cho header
- `WithTitle` - Tiêu đề sheet Excel

**Properties:**
- `$scheduleData` - Collection dữ liệu lịch học
- `$startDate` - Ngày bắt đầu
- `$endDate` - Ngày kết thúc
- `$user` - Thông tin user
- `$className` - Tên lớp

**Khác biệt so với InstructorScheduleExport:**
- Không có cột "Tên lớp" (học sinh chỉ học một lớp)
- Hiển thị "Giảng viên - Phòng" thay vì "Lớp - Phòng"
- Không cần merge cell theo lớp

## Technical Notes

### Query Performance

- Sử dụng eager loading để tránh N+1 queries:
  ```php
  ->with(['subject', 'instructor', 'classroom.building'])
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

### Active Training Schedule

Module tự động lấy lịch đào tạo đang hoạt động (active training schedule) của học sinh:
```php
TrainingSchedule::where('class_id', $classId)
    ->where('is_active', true)
    ->where('start_date', '<=', $today)
    ->where('end_date', '>=', $today)
    ->first();
```

Nếu không tìm thấy lịch đào tạo đang hoạt động:
- Hiển thị thông báo "Không tìm thấy lịch đào tạo đang hoạt động"
- Không hiển thị lịch học

## Bảo mật

- **Authentication**: Chỉ user đã đăng nhập mới truy cập được
- **Authorization**:
  - Kiểm tra `auth()->user()->isStudent()` trong controller
  - Yêu cầu permission `student-schedule.index`
- **Data Isolation**: Mỗi học sinh chỉ xem/xuất được lịch của lớp mình
  - Filter theo `training_schedule_id` từ active training schedule của lớp
  - Kiểm tra `class_id` từ `auth()->user()->class_id`

## Dependencies

Module này sử dụng:

- **Laravel packages:**
  - `maatwebsite/excel` - Export Excel
  - `spatie/laravel-permission` - RBAC
- **Tables:**
  - `schedule_details`
  - `users`
  - `subjects`
  - `instructors`
  - `classrooms`
  - `buildings`
  - `training_schedules`
  - `classes`
- **Frontend:**
  - Tailwind CSS v4
  - Alpine.js (nếu có)

## Error Handling

Module xử lý các trường hợp lỗi:

1. **Học sinh chưa có lớp:**
   - Message: "Bạn chưa được xếp vào lớp nào"
   - Action: Redirect back

2. **Không có lịch đào tạo đang hoạt động:**
   - Message: "Không tìm thấy lịch đào tạo đang hoạt động cho lớp của bạn"
   - Action: Hiển thị view với `noSchedule = true`

3. **Export validation error:**
   - Validate `start_date` và `end_date` required
   - Validate `end_date >= start_date`

## Support

Liên hệ team development nếu có vấn đề.
