# SPRINT 44 — LMS theo lớp, ẩn nút theo quyền và lọc liên động còn sót

- **Ngày lập:** 2026-08-05
- **Nền:** `feat/lms-roadmap-integration` sau PR #161
- **Nguồn yêu cầu:** phản hồi nghiệm thu 05-08 của chủ nhiệm dự án

Sprint này gom 11 mục phản hồi. Mọi kết luận bên dưới đều đã kiểm chứng trên mã
nguồn hoặc trên app đang chạy, không phải suy đoán.

---

## 0. Hiện trạng đã kiểm chứng

| # | Mục | Kết quả rà soát |
|---|---|---|
| 1 | Bấm logo về `/` không cuộn được | **Đúng.** `layouts/admin.blade.php` đặt `<html class="h-full overflow-hidden">`. Turbo thay `<body>` nhưng **giữ nguyên class trên `<html>`** → trang chủ kế thừa `overflow-hidden`. Reload mới dựng lại `<html>` nên hết. |
| 2 | Khóa LMS phải ghi danh cả lớp + GV dạy môn | **Một phần.** Wizard đã có `class_id` và `syncMembersFromCore`; `LmsCourseProvisioningService::syncInstructorsFromTeachingAssignment()` đã tồn tại. Nhưng wizard chỉ chọn **một** GV thủ công, không kéo toàn bộ GV theo phân công, và **không hiển thị Hệ/Ngành** của lớp để đối chiếu. |
| 3 | Điểm danh mặc định "có mặt" | **Chưa.** `attendance/show.blade.php:40` mặc định `'absent'` — ngược yêu cầu. |
| 3b | Ngưỡng vắng → rớt môn | **Chưa có gì.** Không tìm thấy `absence`/`threshold` trong `modules/Lms` lẫn `modules/Grades`. |
| 4 | Chat LMS | Có `learn/chat.blade.php` + thư mục `Views/chat`. Cần rà UX, chưa kết luận. |
| 5 | SCORM fullscreen | **Chưa có.** `require_fullscreen` chỉ phục vụ **coi thi**, `learn/scorm.blade.php` không có nút fullscreen. |
| 6 | Tạo môn học: chọn Hệ trước | **Đã có** `training_system_id` (dòng 187) nhưng **chưa liên động** để lọc Ngành. |
| 7 | Lọc GV theo mã khoa (K1…K8) khi xếp lịch | **Tạm thời dùng** `substr(subject_code, -2)` để suy ra khoa. Cần refactor: thêm `faculty_code` vào bảng `subjects` và lọc từ đó (xem §2.2). |
| 8 | Quản lý khoa: nút "Cập nhật & Ngày tiếp theo" gây lỗi | Nút `save_and_next` không xét quyền tạo mới → điều hướng sang `create` mà vai trò khoa không có quyền. |
| 9 | Không có quyền thì ẩn nút | Rải rác: nhiều màn hình đã `@can`, một số vẫn hiện. |
| 10 | Sidebar vẫn hiện mục đã bỏ tick | **Đúng.** 5/24 mục không kiểm tra `->can()`: *Lịch giảng dạy của tôi, Giờ chuẩn GV, Thùng rác, Chữ ký số, Database Management*. |
| 11 | Sửa lịch đào tạo: chọn ngành phải lọc lớp | **Đã có** `data-specialization-id` trên `<option>` lớp — cùng kiểu lọc bằng thuộc tính đã hỏng ở màn Bài học (Tom Select bỏ qua `hidden`). Cần chuyển sang lọc server như B4. |

---

## 1. Phân đợt

Chia 4 đợt theo rủi ro và mức phụ thuộc. Mỗi đợt xong thì dừng, báo cáo, chờ duyệt.

### Đợt A — Chặn quyền và lỗi chặn người dùng (P0)

Rủi ro cao nhất: vai trò nhìn thấy hoặc bấm được thứ không được phép.

| Mã | Việc | File chính | Cỡ |
|---|---|---|---|
| A1 | Sidebar ẩn theo quyền — thay 5 điều kiện `isInstructor()/isManagementActor()/isSuperAdmin()` bằng `->can()` của ứng dụng tương ứng | `resources/views/partials/sidebar.blade.php` | S |
| A2 | Ẩn nút Thêm/Sửa/Xóa khi thiếu quyền — rà toàn bộ `x-page-header` và `x-table.action-buttons` | component dùng chung + các `index.blade.php` | M |
| A3 | Khoá "Cập nhật & Ngày tiếp theo" cho vai trò không có quyền tạo tiết | `schedule-detail-form.blade.php:289` | S |
| A4 | Lỗi cuộn khi bấm logo về `/` | `layouts/admin.blade.php` | S |
| A5 | **Khoá sửa lịch của buổi đã học xong** | `ScheduleDetailController` + `schedule-detail-form.blade.php` | M |

**A1 làm trước** vì nó quyết định người dùng *thấy* gì; A2 xử lý cái họ *bấm* được.

**Cách làm A4:** bỏ `overflow-hidden` khỏi `<html>`, chuyển việc khoá cuộn xuống phần tử shell của admin. Cách này an toàn hơn viết JS gỡ class khi Turbo chuyển trang, vì không phụ thuộc vòng đời Turbo.

#### A5 — Khoá sửa lịch của buổi đã học xong

Hiện trạng: **không có bất kỳ khoá theo thời gian nào**. Ai có quyền sửa là sửa được
mọi ngày, kể cả ngày đã trôi qua.

Vướng mắc kỹ thuật cần xử lý trước: `schedule_details` chỉ có `date` và `period`
(số nguyên 1–9), **không có giờ bắt đầu / kết thúc**, và **không tìm thấy bảng giờ
tiết nào** trong `config` lẫn service. Nghĩa là hệ thống hiện **không biết** tiết 3
kết thúc lúc mấy giờ.

Chốt khoá theo **cả ngày** nên không cần bảng giờ tiết. Quy tắc:

```
Ngày học + ân hạn (mặc định 48 giờ) < hiện tại  →  khoá
Super Admin                                      →  vẫn sửa được, có ghi nhật ký
```

Chặn ở **server** (`ScheduleDetailController@update` và `@store`), đồng thời **khoá ô
nhập và ẩn nút Lưu** trên giao diện để người dùng không nhập uổng công. Ẩn nút chỉ là
trải nghiệm, không phải bảo vệ — thiếu chặn server thì gửi thẳng POST vẫn qua.

**DoD A:**
- [ ] Đăng nhập bằng Giảng viên và Quản lý khoa: sidebar không còn mục ngoài quyền
- [ ] Bỏ tick một ứng dụng trong ma trận → mục biến mất khỏi sidebar ngay
- [ ] Vai trò chỉ có quyền Xem: không thấy nút Thêm/Sửa/Xóa trên mọi màn hình đã rà
- [ ] Bấm logo từ trang quản trị → trang chủ cuộn được, không cần reload
- [ ] Test hồi quy cho từng mục

---

### Đợt B — Lọc liên động còn sót (P1)

Tiếp nối B4 của đợt trước, dùng lại `x-filter-form` với `depends_on`.

| Mã | Việc | Cỡ |
|---|---|---|
| B1 | Tạo/sửa **môn học**: chọn Hệ → lọc Ngành (hiện có ô Hệ nhưng chưa liên động) | S |
| B2 | Sửa **lịch đào tạo**: chọn Ngành → lọc Lớp thuộc ngành, chuyển từ lọc bằng `data-*` sang lọc server | S |
| B3 | Thêm/sửa **lịch học**: lọc giảng viên theo **mã khoa** của môn đang chọn | M |

**Lưu ý B2:** lọc bằng thuộc tính `data-specialization-id` là đúng cái đã hỏng ở màn Bài học — Tom Select không đọc `hidden`. Phải lọc phía server như đã làm ở B4.

### 2.2 B3 — Kiến trúc dữ liệu: Phân công Khoa cho môn học (đề xuất 06-08-2026)

**Hiện trạng vấn đề:**

Hệ thống hiện đang sử dụng **2 ký tự cuối của mã môn học** để suy ra mã khoa (ví dụ: `MON_01_K7` → `K7`). Cách này không đảm bảo tính linh hoạt và không tuân theo chuẩn thiết kế dữ liệu:

- ❌ Không thể chuyển môn học sang khoa khác nếu chỉ dựa vào mã
- ❌ Khi môn học thuộc NVQY (các khoa chuyên biệt) không rõ gán cho khoa nào
- ❌ Nếu mã môn học đổi theo quy ước của Bộ trong tương lai, logic sẽ vỡ
- ❌ Không tuân theo quy tắc thiết kế dữ liệu chuẩn (cấu trúc phải rõ ràng, không dẫn xuất)
- ❌ Các trường đại học khác không dùng quy ước này
- ❌ Thống kê và báo cáo sau này khó maintain

**Giải pháp đề xuất:**

1. **Thêm trường `faculty_code` vào bảng `subjects`**
   - Lưu mã khoa mà môn học này được phân công dạy
   - Ví dụ: `MON_01` → `faculty_code = 'K7'`
   - Cho phép `NULL` để hỗ trợ các khoa ngoài (NVQY) chưa gán

2. **Thêm trường `abbreviation` vào bảng `units` (Khoa)**
   - Tên viết tắt của khoa để dùng trong báo cáo/thống kê (hiện tên đầy đủ dài)
   - Ví dụ: `Khoa Công Nghệ Thông Tin` → `abbreviation = 'CNTT'`

3. **Tạo chức năng "Phân công giảng dạy cho Khoa"** (hoặc tích hợp vào quản lý môn học)
   - Cho phép: Chọn Hệ → Chọn Ngành → Chọn Môn → Phân công cho Khoa
   - Thực chất là cập nhật `faculty_code` trong bảng `subjects`
   - Giao diện tương tự như "Phân công giảng dạy" cho Giảng viên

4. **Khi truy vấn xếp lịch (B3)**, lọc giảng viên dựa trên `faculty_code` trong bảng `subjects`
   - Thay vì: `substr(subject_code, -2) = 'K7'`
   - Sử dụng: `subjects.faculty_code = 'K7'`

**Hướng thực hiện:**

**Nếu dữ liệu môn học hiện tại còn ít:**
- Tạo migration thêm `faculty_code` (nullable)
- Cập nhật từng môn thủ công hoặc qua UI

**Nếu dữ liệu hiện tại nhiều rồi:**
- Tạo migration thêm `faculty_code` (nullable)
- Viết seeder/migration script để fill giá trị mặc định:
  ```php
  // Lấy 2 ký tự cuối của mã môn học làm faculty_code
  DB::statement('
      UPDATE subjects 
      SET faculty_code = RIGHT(code, 2) 
      WHERE faculty_code IS NULL
  ');
  ```
- Sau đó có thể sửa thủ công các trường hợp đặc biệt (NVQY, v.v.)
- Cấu trúc dữ liệu lúc này tuân thủ chuẩn, dễ bảo trì và mở rộng

**Mối quan hệ dữ liệu sau khi áp dụng:**
```
Units (Khoa)
├─ id, code (K1…K8, NVQY, v.v.), name, abbreviation ← NEW
└─ ...

Subjects (Môn học)
├─ id, code (MON_01, v.v.), name, training_system_id
├─ faculty_code (FK → Units.code) ← NEW
└─ ...

Teachers (Giảng viên)
├─ id, name, unit_id (FK → Units.id)
└─ ...

Khi xếp lịch (B3):
  SELECT teachers.* FROM teachers
  WHERE teachers.unit_id = (
      SELECT id FROM units 
      WHERE code = (SELECT faculty_code FROM subjects WHERE id = ?)
  )
```

**Lợi ích:**
- ✅ Rõ ràng, tuân theo chuẩn dữ liệu
- ✅ Linh hoạt: có thể chuyển môn sang khoa khác bằng cách đổi `faculty_code`
- ✅ Hỗ trợ các trường hợp đặc biệt (NVQY)
- ✅ Thống kê/báo cáo dễ maintain
- ✅ Không phụ thuộc vào quy ước mã môn học (chịu được đổi mã theo Bộ)

**DoD B:**
- [ ] Chọn Hệ → danh sách Ngành co lại đúng
- [ ] Chọn Ngành → danh sách Lớp chỉ còn lớp thuộc ngành
- [ ] Migration: thêm `faculty_code` vào `subjects` + `abbreviation` vào `units`
- [ ] Seeder: fill `faculty_code` từ 2 ký tự cuối của mã môn (trước khi chuyển qua cấu trúc phân công)
- [ ] Chọn môn có `faculty_code = 'K7'` → danh sách GV chỉ còn GV khoa K7
- [ ] Test HTTP thật, không phụ thuộc JavaScript

---

### Đợt C — LMS theo lớp và điểm danh (P1)

Đây là phần thay đổi nghiệp vụ, cần chốt vài điểm trước khi code (xem §2).

| Mã | Việc | Cỡ |
|---|---|---|
| C1 | Wizard tạo khóa hiển thị **Hệ → Ngành → Lớp → Môn** liên động; nêu rõ sẽ ghi danh bao nhiêu học viên | M |
| C2 | Ghi danh **cả lớp** thay vì từng cá nhân; nút đồng bộ lại khi lớp thay đổi | M |
| C3 | Kéo **toàn bộ GV dạy môn** từ `TeachingAssignment` (mở rộng `syncInstructorsFromTeachingAssignment`, hiện chỉ gán 1 lead) | M |
| C4 | Điểm danh mặc định **có mặt**; chỉ gạt công tắc cho người vắng | S |
| C5 | **Ngưỡng vắng → rớt môn**: cấu hình theo môn, cảnh báo khi vượt, đẩy trạng thái sang Quản lý điểm | L |

**C4 làm trước C5** — C4 nhỏ, thấy kết quả ngay; C5 phụ thuộc dữ liệu điểm danh đúng.

**DoD C:**
- [ ] Tạo khóa mới: chọn lớp → hiện "sẽ ghi danh N học viên", tạo xong đúng N thành viên
- [ ] Mọi GV được phân công dạy môn đều có mặt trong khóa
- [ ] Mở phiên điểm danh lớp 60 học viên: tất cả mặc định *có mặt*, chỉ gạt người vắng
- [ ] Vượt ngưỡng vắng → cảnh báo và đánh dấu; ngưỡng chỉnh được, không hardcode

---

### Đợt D — Rà UX còn lại (P2)

| Mã | Việc | Cỡ |
|---|---|---|
| D1 | Rà **chat LMS**: cuộn, tải thêm, trạng thái gửi, chặn spam | M |
| D2 | Rà **SCORM**: giao diện player, thêm **fullscreen**, thanh tiến độ | M |

**DoD D:**
- [ ] Chat: cuộn mượt khi nhiều tin, không nhảy vị trí khi có tin mới
- [ ] SCORM: có nút toàn màn hình, thoát bằng Esc, tiến độ vẫn ghi nhận

---

## 2. Quyết định nghiệp vụ (đã chốt 05-08-2026)

| Vấn đề | Quyết định | Hệ quả kỹ thuật |
|---|---|---|
| Cách tính ngưỡng vắng | **Phần trăm buổi** | Lưu `absence_limit_percent` (số nguyên 0–100), không lưu số tiết |
| Phạm vi đặt ngưỡng | **Theo môn học** | Thêm cột vào `subjects`; không cần bảng cấu hình riêng |
| Vắng có phép | **Không tính** vào ngưỡng | Công thức chỉ đếm trạng thái vắng không phép; cần tách rõ hai trạng thái ở màn điểm danh |
| Khi vượt ngưỡng | **Chỉ cảnh báo**, giảng viên quyết định | Không tự ghi sang Quản lý điểm. Sinh cảnh báo trong `lms_learning_alerts`, hiển thị ở sổ điểm LMS |
| Lớp thêm học viên giữa kỳ | **Tự đồng bộ** | Dùng lại job `lms:sync-members` đang chạy hằng ngày; cần bảo đảm không xoá thành viên đã có điểm |

Không còn mục nào chặn đợt C.

### 2.1 A5 — khoá sửa lịch đã học (đã chốt 06-08-2026)

| Vấn đề | Quyết định | Hệ quả kỹ thuật |
|---|---|---|
| Mức khoá | **Cả ngày** | So sánh theo `date`, **không cần** khai giờ từng tiết — bỏ được phần `period_times` |
| Ân hạn | **Có** — mặc định **48 giờ**, khai trong `config` | Ngày học vẫn sửa được trong 48h sau khi kết thúc; quá hạn khoá cứng |
| Ngoại lệ | **Chỉ Super Admin** | Mọi lần Super Admin sửa ngày đã khoá đều **ghi nhật ký** (ai, ngày nào, sửa gì) |

Không còn mục nào chặn. A5 gọn hơn dự tính ban đầu vì không phải làm bảng giờ tiết.

### Chi tiết suy ra từ các quyết định trên

- **Trạng thái điểm danh** phải phân biệt tối thiểu: *có mặt · vắng có phép · vắng không phép*.
  Mặc định là **có mặt**; giảng viên chỉ gạt người vắng và chọn có/không phép.
- **Ngưỡng mặc định** khi môn chưa khai: lấy một mức chung trong `config`, môn nào khai thì
  ghi đè. Tránh để `NULL` nghĩa là "không giới hạn" vì dễ bỏ sót.
- **Cảnh báo** nêu rõ: đã vắng bao nhiêu buổi / tổng số buổi / phần trăm / ngưỡng của môn,
  để giảng viên có căn cứ quyết định.
- **Đồng bộ tự động** chỉ được *thêm* thành viên mới và đánh dấu người đã rời lớp;
  tuyệt đối không xoá thành viên đã có điểm danh hoặc điểm số.

---

## 3. Thứ tự đề xuất

```
A (chặn quyền, P0) → B (lọc liên động, P1) → C (LMS theo lớp, P1) → D (UX, P2)
```

Đợt A nên làm ngay: đây là nhóm duy nhất có **rủi ro lộ chức năng ngoài quyền**.
Đợt C là khối lớn nhất và cần chốt §2 trước, nên xếp sau B để không phải chờ.

---

## 4. Ràng buộc chung

- Mọi quyền mới khai trong `ApplicationRegistry`, không tick tay trong DB
- Ngưỡng vắng, hệ số, mốc thời gian **lưu trong DB**, không hardcode
- **Cấu trúc dữ liệu:** phải rõ ràng, không dẫn xuất từ trường khác (ví dụ: không dùng `substr(code, -2)` để suy ra mã khoa, mà lưu trực tiếp `faculty_code`)
- Lọc liên động dùng `x-filter-form` với `depends_on`, không tự chế JS mới
- Trong Blade chỉ dùng **một dạng khối PHP** cho mỗi file — trộn hai dạng làm Blade nuốt đoạn ở giữa (đã gây lỗi 500 ở sổ điểm LMS và template PDF)
- Đổi Blade/Tailwind xong phải chạy `npm run build`, nếu không class mới không có trong CSS
- Mỗi đợt: `php artisan test --testsuite=Feature` xanh + `vendor/bin/pint --dirty`
