# Roadmap status — handoff cho agent tiếp theo

Ngày cập nhật: 2026-08-04<br>
Branch/PR: `feat/lms-roadmap-integration` / [PR #157](https://github.com/tho076/lich-hoc-cdhc2/pull/157)<br>
Commit nền: `28ea92f`

## Trạng thái hiện tại

Sprint 38–43 đã được **làm lại từ đầu (B1–B5)** theo `Bảng phân quyền vai trò.docx`,
vì bản trước gom quyền theo phân hệ nên Quản lý khoa vào Giờ chuẩn GV là full quyền.

- **B1** — `app/Support/ApplicationRegistry.php`: danh mục 5 phân hệ / 39 ứng dụng /
  169 permission. Tách 15 ứng dụng Giờ chuẩn từ `view+manage` thành Xem/Thêm/Sửa/Xóa.
- **B2** — Gỡ fallback quyền tổng ở `StandardHoursBaseController`. `standard-hours.index`
  chỉ còn nghĩa "vào được phân hệ"; mỗi ứng dụng gác bằng quyền của chính nó.
- **B3** — Ma trận phân quyền dựng lại theo layout docx (phân hệ → ứng dụng →
  Xem/Thêm/Sửa/Xóa/Duyệt/Xuất). `app/Support/RoleCatalog.php` khai báo 6 nhóm vai trò
  theo chức trách; thêm `faculty-manager` và `research-agency-manager`.
- **B4** — Lọc liên động dùng chung trong `x-filter-form` qua `depends_on`;
  áp cho Bài học, Môn học, Ngành, Lớp, Lịch đào tạo, Giảng viên, Phân công.
- **B5** — Hub Ngành đào tạo (Hệ → Ngành → Môn → Bài), Mã số thành cột đầu tiên,
  thêm bài học tại chỗ, sửa dòng chú thích bị đè ở form Thêm lịch học.

## Kiểm thử

`php artisan test --testsuite=Feature` → **188 tests passed, 1.387 assertions**.

PHP lint, Pint, Blade cache và `git diff --check` cũng đạt.

## Migration khi deploy

```bash
php artisan migrate
php artisan permissions:sync
php artisan view:clear
```

Migration mới:

- `2026_08_03_000001_isolate_management_role_permissions.php`
- `2026_08_03_000002_add_standard_hours_application_permissions.php`
- `2026_08_04_000001_add_granular_application_permissions.php` — trải quyền tổng cũ
  thành quyền chi tiết; role tự kê khai (`instructor`, `student`) bị loại khỏi bước này.
- `2026_08_04_000002_seed_school_role_groups.php` — tạo nhóm vai trò, **chỉ cấp thêm**,
  không thu hồi cấu hình quản trị viên đã chỉnh tay.

## Đợt 2 (2026-08-04) — Gom vai trò và mở rộng ma trận

- **C1** — Registry lên **6 phân hệ / 62 ứng dụng / 282 permission**. Tách "Người dùng"
  khỏi "Hệ thống"; LMS 1 → 17 ứng dụng, Quản lý điểm 1 → 9 ứng dụng.
- **C2** — `app/Support/ApplicationGate.php`: gác quyền theo từng ứng dụng cho 13
  controller LMS và `GradeAccess`, vẫn chấp nhận quyền tổng cũ khi chuyển đổi.
- **C3** — Còn đúng **8 vai trò chuẩn**. Gỡ `manager`, `faculty-schedule-manager`,
  `standard-hours-manager`, `approval-agency`; tài khoản được chuyển tự động.
- **C4** — Nối năm học Lịch đào tạo → LMS → Quản lý điểm: FK `grade_books.academic_year_id`,
  backfill `lms_courses.academic_year_id`, bắt buộc chọn năm học khi tạo khóa LMS.
- **C5** — Ma trận phân quyền: chú giải từng cột, thẻ mô tả vai trò kèm phạm vi,
  tìm nhanh ứng dụng, bộ đếm ô đang tick, cột trái ghim.

Tám vai trò: Super Admin · Quản lý toàn trường · Quản lý đào tạo · Quản lý khoa
(lọc theo đơn vị) · Quản lý khảo thí · Quản lý Khoa học quân sự · Giảng viên · Học viên.

`php artisan test --testsuite=Feature` → **198 passed, 1.489 assertions**.

## Backlog tiếp theo

1. **Nghiệm thu phân quyền bằng tài khoản thật** (Giảng viên, Quản lý khoa) — đợt 2
   mới chỉ kiểm tra bằng super-admin nên chưa chứng minh được chặn đúng chỗ.
2. **Nghiệm thu luồng LMS → Quản lý điểm → Dashboard** với dữ liệu thật: tạo khóa →
   điểm danh → chấm bài → chuyển điểm → đối chiếu số liệu Dashboard.
3. Dọn 6 chỗ code còn tham chiếu vai trò `approval-agency` đã gỡ.
4. Chạy CI/staging và nghiệm thu migration trên môi trường thật.
2. Kiểm thử ma trận quyền sau khi migrate dữ liệu role cũ; sau khi các role cũ
   chuyển đổi xong thì gỡ hẳn quyền gộp `<ứng dụng>.manage` và alias legacy.
3. Chốt phân quyền chi tiết cho Quản lý Ban KT / Ban KHQS (hiện là mặc định do
   `RoleCatalog` suy ra, docx mới có mục "1. Quản lý khoa").
4. Bổ sung regression test cho import file lớn, xuất LHL Word/PDF và công thức Giờ chuẩn.
5. `REVIEW_CHECKLIST.md` đang rỗng — dựng lại nội dung.
6. Rà các khoản nợ kỹ thuật trong `docs/CODE_JOURNAL.md` khi có yêu cầu.

## Quy tắc handoff

- Không commit `Bảng phân quyền vai trò.docx` và `DANH MỤC NGÀNH ĐÀO TẠO.docx`.
- Không tự chạy migration production nếu chưa có backup/cửa sổ triển khai.
- Permission mới phải khai báo qua `SyncPermissionsAndRoles.php` và migration tương ứng.
- Khi hoàn thành backlog, cập nhật file này và `docs/CODE_JOURNAL.md`.
