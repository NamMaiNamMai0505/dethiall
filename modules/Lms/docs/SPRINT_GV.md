# LMS — Lịch sử sprint dành cho Giảng viên

> Tài liệu này được giữ để đối chiếu lịch sử triển khai. Trạng thái vận hành và hướng dẫn hiện hành xem tại [`docs/HDSD_LMS.md`](../../../docs/HDSD_LMS.md); các mục từng để mở trong tài liệu này không còn là nguồn xác định phạm vi roadmap mới.

**Ngày soạn:** 2026-07-18  
**Project:** `D:\CDHC2\lich-hoc-cdhc2`  
**Mục tiêu:** Portal **riêng cho GV** trên cổng học (`/lms/hoc` hoặc `/lms/gv`) — soạn, chấm, điểm danh, theo dõi lớp **không** phải vào `layouts.admin`.

---

## Hiện trạng (để đối chiếu)

| Hạng mục | Trạng thái |
|----------|------------|
| Đăng nhập GV → `/lms/hoc` | ✅ Có |
| Xem khóa mình phụ trách | ✅ Có (scope `instructor_id` / member lecturer) |
| Phòng học (tabs HV) | ✅ Có — GV vào cùng shell HV |
| Lịch dạy (module riêng) | ✅ Có — `/instructor-schedule` |
| Khóa / mở chat khóa | ✅ Có (moderate) |
| Tạo khóa LMS | ❌ Không (`lms.create` không gán instructor) |
| UI soạn bài / BT / thi / chấm ngay portal GV | ❌ Chưa (chỉ route admin nếu biết URL) |
| Dashboard quản trị lớp GV | ❌ Chưa |
| Báo cáo lớp / xuất điểm | ❌ Chưa |

**Quyền hiện tại role `instructor`:**  
`instructor-schedule.index/show`, `dashboards.index`, `lms.index`, `lms.show`, `lms.edit`.

---

## Nguyên tắc triển khai

1. **Không** ép GV dùng sidebar admin “Quản lý đào tạo”.
2. Mọi thao tác quản lý lớp nằm trong shell LMS (teal) hoặc sub-shell `/lms/gv/*`.
3. Scope cứng: chỉ khóa `instructor_id = me` **hoặc** member `lecturer`.
4. Học viên **không** thấy menu/soạn của GV.
5. Admin/PDOT/Khoa vẫn dùng Hub `/lms` như hiện tại.

---

## Sprint GV-1 — Portal GV + điều hướng (nền tảng)

**Mục tiêu:** GV có “nhà” riêng, tách rõ với HV.

### Deliverables
- [x] Entry `/lms/gv` khi `isInstructor()`
- [x] Navbar GV: **Khóa dạy** · **Lịch dạy** · **Thông báo** · **Cá nhân** · Thoát  
- [x] Trang “Khóa dạy”: card + badge HV / chờ chấm / thi mở
- [x] Nút **Vào lớp (dạy)** → `?mode=teach`
- [x] `LmsAccess::canTeachCourse()` + redirect login → `lms.teach.home`
- [x] Seeder: `giangvien@example.com` / `password` gắn khóa demo

### Tiêu chí xong
- [x] Login GV không rơi vào Hub admin.
- [x] Chỉ thấy khóa mình phụ trách.

### Ước lượng: **2–3 ngày**

---

## Sprint GV-2 — Soạn nội dung khóa (bài học + tài liệu)

**Mục tiêu:** Soạn bài ngay portal GV.

### Deliverables
- [x] Tab/panel **Soạn bài** (mode teach): CRUD bài học
- [x] Upload tài liệu gắn bài; SCORM (.zip)
- [x] Sắp xếp ↑↓ `sort_order`
- [x] Publish / unpublish bài & học liệu
- [ ] Map bài CTĐT (`subject_lesson_id`) — deferred

### Tiêu chí xong
- [x] GV tạo bài + file trong portal teach; HV thấy sau publish.

### Ước lượng: **3–4 ngày**

---

## Sprint GV-3 — Bài tập & chấm điểm

**Mục tiêu:** Giao BT theo bài, chấm, feedback.

### Deliverables
- [x] Tạo/sửa/xoá/toggle BT: gắn bài, hạn, max, nộp muộn
- [x] Danh sách bài nộp + form chấm (điểm + feedback)
- [x] Notify HV (system_notifications) khi chấm
- [x] HV xem điểm tab Bài tập / Điểm (đã có portal HV)
- [ ] Filter chưa chấm only (UI gộp đủ — optional polish)
- [ ] Nộp lại sau feedback (optional)

### Tiêu chí xong
- [x] HV nộp → GV chấm portal teach → HV thấy điểm.

### Ước lượng: **3–4 ngày**

---

## Sprint GV-4 — Thi online & giám sát cơ bản

**Mục tiêu:** GV tạo đề / xem kết quả trong portal.

### Deliverables
- [x] Tạo NHCH (MCQ / Đ-S / ngắn) trong khóa — tab **Thi online**
- [x] Tạo đề thi: thời gian, số lần, mở/đóng, shuffle, proctor basic
- [x] Gắn câu hỏi từ NHCH
- [x] Xem attempt: điểm, thời gian, blur_count
- [x] Xuất CSV điểm thi

### Tiêu chí xong
- [x] GV tạo đề trên portal teach, HV làm, GV xem lượt + CSV.

### Ước lượng: **4–5 ngày**

---

## Sprint GV-5 — Điểm danh lớp (GV chủ trì)

**Mục tiêu:** GV mở điểm danh theo ngày, điểm tay / xem HV self-check.

### Deliverables
- [x] Tạo session theo **ngày** (title, mode: manual/self/qr)
- [x] Mở/đóng / mở lại session
- [x] Bảng điểm danh: HV + present/absent/late/excused (bulk save)
- [x] % chuyên cần từng HV
- [x] Token self/QR hiển thị khi mode tương ứng
- [x] Nút “Cả lớp có mặt”
- [x] QR popup (link token) + cấu hình MAC AP / IP CIDR Wi‑Fi trường
- [x] Check-in QR chỉ OK khi IP thuộc dải trường (cập nhật MAC khi đổi router)
- [x] Fallback điểm miệng (manual) khi không Wi‑Fi

### Tiêu chí xong
- [x] GV mở ngày → điểm tay / HV self → % chuyên cần cập nhật.

### Ước lượng: **2–3 ngày**

---

## Sprint GV-6 — Bảng điểm, tiến độ, cảnh báo, khảo sát, chứng chỉ (nhìn lớp)

**Mục tiêu:** GV theo dõi cả lớp, không chỉ “xem như HV”.

### Deliverables
- [x] **Bảng điểm lớp**: hàng = HV, cột = BT + thi + chuyên cần + tổng
- [ ] Override điểm (ghi chú) nếu cần — deferred (dùng admin gradebook)
- [x] **Tiến độ lớp**: % hoàn thành + chuyên cần + CC
- [x] **Cảnh báo**: danh sách cảnh báo mở theo HV
- [x] **Khảo sát**: xem TB rating + số phản hồi
- [x] **Chứng chỉ**: cột đã cấp / chưa

### Tiêu chí xong
- [x] Màn **Lớp học** (tab `class`) đủ số liệu theo dõi lớp.

### Ước lượng: **4–5 ngày**

---

## Sprint GV-7 — Chat / diễn đàn / thông báo (quản trị lớp)

**Mục tiêu:** GV điều hành tương tác lớp.

### Deliverables
- [x] Khóa/mở chat (đã có) + xóa tin nhắn (mới)
- [x] Ghim / khóa chủ đề diễn đàn
- [x] Gửi thông báo lớp (system_notifications → toàn HV khóa) — tab **Tương tác**
- [x] Wi‑Fi MAC chuyển admin sidebar (`/campus-network`) — không set trên portal GV

### Tiêu chí xong
- [x] GV gửi 1 thông báo, HV thấy chuông LMS.

### Ước lượng: **2–3 ngày**

---

## Sprint GV-8 — Lịch dạy tích hợp + polish UX

**Mục tiêu:** Một cổng đủ dùng hàng ngày.

### Deliverables
- [x] Tab/page **Lịch dạy** trong LMS (`/lms/gv/schedule`)
- [x] Deep-link từ lịch → khóa LMS (map subject/class + instructor)
- [x] Navbar sticky + link **Dashboard** (giờ chuẩn) trên portal GV
- [x] Tải bài nộp: từng HV + ZIP cả lớp
- [x] Tom Select + Flatpickr form teach (+ re-init khi đổi tab)
- [x] Profile GV (mã GV, đơn vị, SĐT)
- [x] Empty states trang khóa dạy (đã có)
- [ ] Permission denied page riêng (polish optional)
- [ ] Feature test PHPUnit scope GV (optional)

### Tiêu chí xong
- [x] GV xem lịch dạy ngay LMS; bấm tiết → vào khóa nếu đã map.

### Ước lượng: **2–3 ngày**

---

## Thứ tự đề xuất triển khai

```
GV-1 (portal) → GV-2 (soạn) → GV-3 (BT/chấm) → GV-5 (điểm danh)
     → GV-6 (lớp/điểm) → GV-4 (thi) → GV-7 (tương tác) → GV-8 (lịch + polish)
```

- **MVP dùng được tuần đầu:** GV-1 + GV-2 + GV-3 + GV-5  
- **Đủ “lớp học số”:** thêm GV-6 + GV-4  
- **Hoàn thiện:** GV-7 + GV-8  

---

## Ngoài phạm vi (cố ý)

- Tạo CTĐT / môn / lớp (vẫn admin)
- Phân công giảng dạy toàn khoa (Teaching Assignment)
- SSO, mobile app native
- Live video conference

---

## Tài khoản test (sau `php artisan db:seed --class=LmsDemoSeeder`)

| Vai trò | Email | Mật khẩu | Ghi chú |
|---------|-------|----------|---------|
| Super-admin | `admin@example.com` | `password` | Hub `/lms` |
| **Giảng viên 1** | `giangvien@example.com` | `password` | Phụ trách khóa **LMS-DEMO-001** |
| Giảng viên 2 | `gv2@example.com` | `password` | Có nếu DB có ≥ 2 instructor |
| Học viên 1 | `hocvien@example.com` | `password` | Đủ data demo |
| Học viên 2 | `hv2@example.com` | `password` | Tiến độ thấp hơn |

**Cổng GV/HV:** `/lms/hoc`  
**Khóa demo:** code `LMS-DEMO-001`  
**Admin course:** `/lms/courses/{id}`

---

## Checklist review trước khi code từng sprint

- [ ] Xác nhận route prefix: `/lms/gv` hay `?mode=teach` trên course room?
- [ ] GV có được **tạo khóa** không hay chỉ soạn khóa được gán?
- [ ] Chứng chỉ: GV cấp thẳng hay chỉ đề xuất admin?
- [ ] Thông báo: bắt buộc mail hay chỉ in-app?

→ Chốt 4 ý trên rồi bắt đầu **GV-1**.
