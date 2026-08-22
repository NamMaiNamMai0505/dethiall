# Smoke-test report — CDHC2

**Ngày chạy:** 2026-07-20 (re-run full green)  
**Môi trường code:** `D:\CDHC2\lich-hoc-cdhc2`  
**Laravel:** 12.38.1 · **PHP:** 8.4 · **DB migrate:** batch 36 (P2 campus ran)

> Đây là **smoke kỹ thuật tự động** (PHPUnit + artisan + HTTP login).  
> **Không** thay smoke tay theo role (super-admin / GV / HV) trên UI đầy đủ.

---

## 1. Kết quả tổng

| Hạng mục | Kết quả | Chi tiết |
|----------|---------|----------|
| `php artisan test` | ✅ **82 passed (301 assertions)** | 2026-07-20 — đã vá InstructorSchedule |
| Campus P0–P2 + LMS S6/8/9 | ✅ | Toàn bộ pass |
| Migrations | ✅ | Tất cả Ran (gồm campus P1/P2, LMS, StandardHours) |
| Routes mốc | ✅ | `campus-network/*`, `lms`, `lms/hoc`, `lms/gv`, `standard-hours/*` |
| HTTP login local | ✅ | `http://127.0.0.1:8000/login` → **200** |
| HTTP login prod | ⚠️ | `https://tkb.cdhc2.edu.vn/login` → **468** (WAF/proxy — không vào được từ đây) |
| Template LHL xlsx | ✅ | `resources/templates/lhl/Lich_Huan_Luyen_template.xlsx` có file |
| Ảnh chữ ký LHL | ✅ | `storage/app/public/signatures/lhl/*.png` (ít nhất 1 file check OK) |
| Mẫu Word khoa | ✅ | `Mau_Xuat lich_Khoa_Theo lop.docx` (repo root) |

---

## 2. PHPUnit chi tiết

### Pass (trọng tâm)

| Suite | Nội dung |
|-------|----------|
| `CampusNetworkTest` + P0/P1/P2 Feature | CIDR, TrustProxies UI, probe, QR TTL, GPS, stats |
| `LmsCampusP2Test` | Bán kính GPS campus |
| `LmsSprint6TeachTest` | Scope GV NHCH + grade override |
| `LmsSprint8OpsTest` | 403, wizard perm, sync job, resubmit, export multi, throttle |
| `LmsSprint9OpsTest` | Version BT, SCORM commit, proctor, prune, survey templates |
| `ExampleTest` | `/` → redirect login |

### Fail — đã vá (2026-07-20)

| Suite | Trước | Sau |
|-------|-------|-----|
| `InstructorScheduleTest` (9) | thiếu `InstructorFactory` + calendar off-by-one 6 ngày | ✅ factories + fix `diffInDays+1` + `dateRangeLabel` |

**Lệnh full suite:**
```bash
php artisan test
```

---

## 3. Smoke tay đề xuất (30–40 phút) — chưa chạy browser automation

Làm trên local `php artisan serve` + `npm run dev` (hoặc staging), đăng nhập:

### Super-admin
1. `/dashboard` load  
2. `/lms` hub + wizard tạo khóa  
3. `/campus-network` + Test IP + Stats  
4. `/training-schedules` calendar → Xuất LHL Excel/Word  
5. `/standard-hours` hub + 1 màn tính  
6. `/users` import template download  

### GV demo
1. Login → redirect `/lms/gv`  
2. Mở khóa `?mode=teach`: soạn 1 bài, 1 BT, tạo điểm danh QR  
3. Làm mới QR, xem TTL  
4. Tab lớp: override điểm (nếu có HV)  

### HV demo
1. Login → `/lms/hoc`  
2. Phòng học: xem TL, nộp BT (nếu mở)  
3. Điểm danh (self/QR) — cần Wi‑Fi/CIDR/GPS đúng môi trường  

---

## 4. Giới hạn smoke này

- Không seed full demo production  
- Không export file Excel mở bằng Excel/Word để verify gạch chéo  
- Không verify probe LAN / GPS thật ngoài campus  
- Prod 468 = chặn edge; smoke prod cần VPN/IP allowlist  

---

## 5. Hành động sau smoke

1. Sửa hoặc skip `InstructorScheduleTest` (thêm `InstructorFactory` hoặc rewrite create thủ công)  
2. Smoke tay 3 role trên staging  
3. IT: set `TRUSTED_PROXIES`, CIDR, probe URL, HTTPS GPS  
