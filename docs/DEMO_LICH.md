# Demo lịch đầy đủ (ScheduleDemoSeeder)

## Chạy seed

```bash
# DB đã có core (users, classes, subjects, instructors, classrooms, roles)
php artisan db:seed --class=ScheduleDemoSeeder
```

Idempotent: xóa lịch `DEMO-LH-*` cũ rồi tạo lại (~240 tiết / 2 lớp).

## Tài khoản (password: `password`)

| Email | Vai trò | Dùng để |
|-------|---------|---------|
| `admin@example.com` | super-admin | Xuất kế hoạch / lịch HT, quản trị |
| `giangvien@example.com` | instructor (GV-0001) | LMS `/lms/gv/schedule` lịch giảng |
| `gv2@example.com` | instructor (GV-0002) | Lịch giảng GV 2 |
| `hocvien@example.com` | student (lớp Y53) | LMS `/lms/hoc/schedule` lịch học |
| `hv2@example.com` | student (lớp K1) | Lịch học lớp 2 |

## Dữ liệu tạo ra

| Mục | Nội dung |
|-----|----------|
| Lịch HT | `DEMO-LH-A` (lớp Y53, sáng tiết 1–4), `DEMO-LH-B` (lớp K1, chiều tiết 6–9) |
| Khoảng ngày | ~8 tuần quanh tháng hiện tại (Mon–Fri, LT/TH/tự học/thi) |
| Năm học | Tự suy (vd `2025-2026`) |
| LMS | Khóa `LMS-DEMO-{mã_lớp}-S{subject_id}` map subject+class+GV (deep-link lịch) |

## Checklist test

1. **Xuất lịch / kế hoạch HT (admin)**  
   Đăng nhập admin → Lịch đào tạo → chọn `DEMO-LH-A` / `DEMO-LH-B`  
   Khoảng ngày gợi ý: in ra console khi seed (vd `2026-06-22` → `2026-08-16`)  
   Xuất Word lịch HT / kế hoạch khoa.

2. **Lịch giảng GV (LMS)**  
   `giangvien@example.com` → `/lms/gv/schedule` → tháng hiện tại có chấm xanh, bấm ngày xem tiết, link khóa LMS.

3. **Lịch học HV (LMS)**  
   `hocvien@example.com` → `/lms/hoc/schedule` → lịch lớp Y53.

4. **Lịch admin module**  
   Instructor schedule / Student schedule (nếu còn dùng) cùng nguồn `schedule_details`.
