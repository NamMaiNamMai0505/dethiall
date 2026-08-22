# 🚀 Hướng dẫn Deploy InstructorSchedule Module lên PROD

## Tóm tắt tính năng

Module mới cho phép **Giảng viên xem lịch giảng dạy** của riêng họ với các bộ lọc:
- Khoảng thời gian (từ ngày - đến ngày)
- Loại tiết (lý thuyết, thực hành, tự học, thi)
- Buổi học (sáng: tiết 1-5, chiều: tiết 6-9)

---

## ✅ Các file đã thay đổi

### 📁 Files mới tạo:
```
modules/InstructorSchedule/
├── Controllers/InstructorScheduleController.php
├── Views/index.blade.php
├── Routes/web.php
├── Providers/InstructorScheduleServiceProvider.php
└── README.md

database/migrations/
└── 2025_11_18_140759_add_instructor_schedule_permissions.php

database/seeders/
└── InstructorSchedulePermissionSeeder.php (backup, không cần dùng)

tests/Feature/
└── InstructorScheduleTest.php
```

### 📝 Files đã sửa:
```
bootstrap/providers.php                    # Thêm InstructorScheduleServiceProvider
modules/ModuleServiceProvider.php          # Import service provider
app/Services/PermissionService.php         # Thêm 'instructor-schedule' vào resources
resources/views/partials/sidebar.blade.php # Thêm menu "Lịch giảng dạy của tôi"
```

### ⚠️ Files KHÔNG nên deploy thay đổi:
```
database/seeders/RolePermissionSeeder.php  # Chỉ dùng khi init project, KHÔNG chạy trên PROD
```

---

## 🔧 Các bước Deploy lên PROD

### 1️⃣ Pull code về server

```bash
cd /path/to/project
git pull origin main  # hoặc branch bạn đang dùng
```

### 2️⃣ Cài đặt dependencies (nếu có)

```bash
composer install --no-dev --optimize-autoloader
```

### 3️⃣ Chạy migration để thêm permissions

```bash
php artisan migrate
```

**Kết quả mong đợi:**
```
INFO  Running migrations.

2025_11_18_140759_add_instructor_schedule_permissions .......... DONE
```

Migration này sẽ:
- ✅ Tạo 5 permissions: `instructor-schedule.{index,show,create,edit,delete}`
- ✅ Gán `instructor-schedule.index` và `instructor-schedule.show` cho instructor role
- ✅ Sync tất cả permissions cho super-admin và admin roles
- ✅ **KHÔNG ảnh hưởng** đến users và permissions hiện tại

### 4️⃣ Clear tất cả cache

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
php artisan permission:cache-reset
```

### 5️⃣ Optimize (tuỳ chọn, khuyến nghị cho PROD)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ✅ Kiểm tra sau khi deploy

### 1. Kiểm tra route đã được đăng ký

```bash
php artisan route:list --path=instructor-schedule
```

**Kết quả mong đợi:**
```
GET|HEAD  instructor-schedule  instructor-schedule.index
```

### 2. Kiểm tra permissions đã được tạo

Vào database và query:

```sql
SELECT name FROM permissions WHERE name LIKE 'instructor-schedule%';
```

**Kết quả mong đợi:**
```
instructor-schedule.index
instructor-schedule.show
instructor-schedule.create
instructor-schedule.edit
instructor-schedule.delete
```

### 3. Kiểm tra instructor role đã có permissions

```sql
SELECT p.name
FROM permissions p
JOIN role_has_permissions rhp ON p.id = rhp.permission_id
JOIN roles r ON rhp.role_id = r.id
WHERE r.name = 'instructor' AND p.name LIKE 'instructor-schedule%';
```

**Kết quả mong đợi:**
```
instructor-schedule.index
instructor-schedule.show
```

### 4. Kiểm tra UI trên browser

1. Đăng nhập với tài khoản có `user_type = 'instructor'`
2. Kiểm tra sidebar menu có hiển thị **"Lịch giảng dạy của tôi"** (icon calendar-check)
3. Click vào menu, kiểm tra:
   - Trang load thành công
   - Hiển thị statistics cards
   - Hiển thị filter form
   - Hiển thị bảng lịch (hoặc empty state nếu chưa có lịch)
4. Test các filters:
   - Chọn khoảng thời gian
   - Chọn loại tiết
   - Chọn buổi (sáng/chiều)

### 5. Kiểm tra non-instructor users KHÔNG thấy menu

1. Đăng nhập với tài khoản `user_type != 'instructor'`
2. Kiểm tra sidebar **KHÔNG** hiển thị "Lịch giảng dạy của tôi"
3. Thử truy cập trực tiếp `/instructor-schedule` → Phải nhận **403 Forbidden**

---

## 🔄 Rollback (nếu cần)

Nếu có vấn đề và cần rollback:

```bash
php artisan migrate:rollback --step=1
```

Lệnh này sẽ:
- ❌ Xóa toàn bộ permissions của instructor-schedule
- ✅ KHÔNG ảnh hưởng đến module code (chỉ xóa permissions)
- ✅ KHÔNG ảnh hưởng đến permissions khác

Sau đó cần revert code:
```bash
git revert <commit-hash>
```

---

## 🐛 Troubleshooting

### Vấn đề 1: Menu không hiển thị

**Nguyên nhân:** Cache chưa clear hoặc user không có permission

**Giải pháp:**
```bash
php artisan permission:cache-reset
php artisan view:clear
```

Hoặc kiểm tra:
```sql
-- Kiểm tra user có instructor role không
SELECT r.name
FROM roles r
JOIN model_has_roles mhr ON r.id = mhr.role_id
WHERE mhr.model_id = <user_id> AND mhr.model_type = 'App\\Models\\User';

-- Kiểm tra instructor role có permissions không
SELECT p.name
FROM permissions p
JOIN role_has_permissions rhp ON p.id = rhp.permission_id
JOIN roles r ON rhp.role_id = r.id
WHERE r.name = 'instructor';
```

### Vấn đề 2: Route không tồn tại (404)

**Nguyên nhân:** Route cache hoặc service provider chưa được load

**Giải pháp:**
```bash
php artisan route:clear
php artisan config:clear
composer dump-autoload
```

### Vấn đề 3: 403 Forbidden khi truy cập

**Nguyên nhân:** User không phải instructor hoặc thiếu permissions

**Giải pháp:**
- Kiểm tra `user_type = 'instructor'` và `instructor_id IS NOT NULL`
- Kiểm tra instructor role có permissions `instructor-schedule.index`

### Vấn đề 4: View không tìm thấy

**Nguyên nhân:** Service provider chưa được đăng ký

**Giải pháp:**
- Kiểm tra `bootstrap/providers.php` có `InstructorScheduleServiceProvider::class`
- Chạy `composer dump-autoload`
- Clear cache

---

## 📊 Monitoring sau Deploy

Theo dõi trong 1-2 ngày sau deploy:

1. **Error logs**: Kiểm tra có errors liên quan đến instructor-schedule không
2. **Performance**: Query có chậm không (có index `uq_instructor_period` sẵn)
3. **User feedback**: Thu thập feedback từ giảng viên

---

## 📞 Contact

Nếu gặp vấn đề, liên hệ:
- Development Team
- Document này: `DEPLOYMENT_GUIDE_INSTRUCTOR_SCHEDULE.md`

---

**✅ Checklist Deploy:**

- [ ] Pull code về server
- [ ] Chạy `composer install` (nếu cần)
- [ ] Chạy `php artisan migrate`
- [ ] Clear all cache
- [ ] Kiểm tra route list
- [ ] Kiểm tra permissions trong DB
- [ ] Test với tài khoản instructor
- [ ] Test với tài khoản non-instructor (phải 403)
- [ ] Monitor logs
