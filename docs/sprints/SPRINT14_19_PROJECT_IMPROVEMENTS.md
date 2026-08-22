# Roadmap Sprint 14–19 — Ổn định và hoàn thiện LMS

**Ngày lập:** 2026-07-25
**Phạm vi:** toàn hệ thống Dashboard, LMS, Quản lý điểm và Template Engine
**Mốc tiếp nối:** Sprint 6–13 đã triển khai; kế hoạch này bắt đầu từ Sprint 14.

## 1. Baseline hiện tại

- `php artisan test`: **128 tests, 509 assertions — pass**.
- `npm run build`: pass.
- `npm audit --omit=dev`: **0 vulnerability**.
- Không còn lời gọi `alert()`, `confirm()`, `prompt()` native trong mã nguồn nghiệp vụ.
- Có **538 routes**, không trùng method + URI; còn 2 route redirect có cùng tên rỗng `standard-hours.`.
- `composer audit --locked`: **38 security advisories trên 14 package**, gồm advisory mức High.
- `composer.lock` đã được track nhưng `.gitignore` vẫn còn rule bỏ qua gây hiểu nhầm; build production chưa dùng đầy đủ chế độ locked/frozen.
- Pipeline hiện build/deploy thẳng từ `main`, chưa có job bắt buộc chạy PHPUnit, Vite build và security audit trước deploy.
- Migration `2026_07_25_000002_add_global_special_schedule_subjects` đang Pending trên database hiện tại.
- Template Engine đã có parser/runtime Word + Excel, lifecycle, preview, Data Explorer và binding editor; tuy nhiên registry mới có Data Provider cho `lhl.training_plan`.
- `GradeExportService` vẫn dựng Excel bằng code và chưa dùng template Active; tham số `$templateId` hiện không được sử dụng.
- `DocumentConverterInterface` chưa có implementation PDF.
- Còn scaffold/trùng trách nhiệm: `TemplateManagement`, `ExportTemplatesController`, model `ExportTemplates`, một số Update Request chưa có rule.
- Một số file quá lớn, khó bảo trì: LMS course view, TrainingScheduleController, import modal, calendar view.
- Roadmap tài liệu chưa phản ánh Sprint 10–13; file `TODO.md` vẫn ở trạng thái planning cũ.

## 2. Nguyên tắc chung

1. Sprint nào cũng phải giữ export cũ hoạt động cho tới khi nhánh template mới qua UAT.
2. Không tắt fallback production trong cùng sprint vừa thêm engine/provider.
3. Mỗi feature export chỉ được Active khi có Data Provider và schema binding hợp lệ.
4. Dashboard, LMS và Quản lý điểm tiếp tục dùng đúng shell/popup/design riêng.
5. Không nâng Laravel major trong chuỗi sprint này; chỉ nâng bản vá/minor tương thích để giảm rủi ro.
6. Không xóa scaffold hoặc file mẫu khi chưa xác định chắc không còn route, provider, test hay dữ liệu tham chiếu.
7. Mỗi sprint kết thúc bằng test, build, audit và cập nhật tài liệu.

---

## Sprint 14 — Security và reproducible release (P0)

### Mục tiêu

Khóa các rủi ro có thể ảnh hưởng production trước khi triển khai thêm chức năng.

### Công việc

| ID | Việc | Ưu tiên |
|---|---|---|
| S14-01 | Bỏ `composer.lock` khỏi `.gitignore`, cập nhật và version hóa lock file | P0 |
| S14-02 | Nâng Laravel 12 và các dependency trong cùng major/minor an toàn để xử lý advisory | P0 |
| S14-03 | Xử lý advisory của PhpSpreadsheet, Guzzle, Symfony, PHPUnit; không dùng ignore vô thời hạn | P0 |
| S14-04 | Tạo CI quality gate: Composer install, PHPUnit, Blade cache, Vite build, Composer/NPM audit | P0 |
| S14-05 | Chỉ cho job build/deploy chạy sau khi quality gate pass | P0 |
| S14-06 | Docker dùng `composer install` theo lock và `npm ci`; cố định version Composer/Node base image | P1 |
| S14-07 | Tag image theo commit SHA/version, không deploy chỉ bằng tag `latest` | P1 |
| S14-08 | Kiểm tra và chạy migration Pending trên môi trường staging; có backup/rollback checklist | P0 |
| S14-09 | Quy hoạch file tạm và file mẫu root vào `docs/reference` hoặc `tests/Fixtures`; thống nhất policy `public/build` | P1 |

### Definition of Done

- [x] `composer.lock` được track và clean install cho kết quả giống nhau.
- [x] Không còn advisory Critical/High; `composer audit` hiện không còn advisory.
- [x] PR/push không thể deploy nếu test, build hoặc audit fail.
- [x] Docker build dùng dependency lock, image có tag bất biến.
- [x] Migration Sprint 13 chạy thử thành công và rollback/re-run được kiểm chứng trên DB test.
- [x] `php artisan test`, `php artisan view:cache`, `npm run build` đều pass.

**Trạng thái:** hoàn thành phần code/config local ngày 2026-07-25. Lần chạy GitHub Actions và rollout production đầu tiên chỉ thực hiện sau khi có commit/push và backup production được xác minh.

### Ngoài phạm vi

- Không nâng Laravel 13.
- Không refactor nghiệp vụ trong sprint cập nhật dependency.

---

## Sprint 15 — Template Engine thực sự dùng chung (P0/P1)

### Mục tiêu

Loại bỏ hardcode export ở Quản lý điểm và chỉ hiển thị những `feature_key` thực sự có khả năng binding.

### Công việc

| ID | Việc | Ưu tiên |
|---|---|---|
| S15-01 | Tạo Feature Catalog thay cho danh sách `feature_key` hardcode trong controller | P0 |
| S15-02 | Tạo `GradeScoreSheetDataProvider` cho `grades.score_sheet` với schema, mock data và real data | P0 |
| S15-03 | Refactor `GradeExportService` dùng `ActiveTemplateResolver` + `TemplateRenderService` | P0 |
| S15-04 | Hỗ trợ template Active Word và Excel cho bảng điểm | P1 |
| S15-05 | Thêm provider `grades.summary` và `grades.transcript` theo nhu cầu xuất hiện có | P1 |
| S15-06 | Chỉ cho Active template khi feature đã đăng ký Data Provider | P0 |
| S15-07 | Chuẩn hóa audit log: rendered, failed, fallback, actor, feature, version | P1 |
| S15-08 | Test integration Active/Fallback cho từng format và kiểm tra scope quyền dữ liệu điểm | P0 |

### Luồng chuẩn

```text
GradeBook / Graduation data
        ↓
Data Provider
        ↓
Active Template Resolver
        ↓
Word / Excel Engine
        ↓
Audit + Download
```

### Definition of Done

- [ ] `GradeExportService` không còn tự dựng layout khi có template Active.
- [x] `grades.score_sheet` có Mock Preview và binding dữ liệu thật.
- [ ] Word/Excel dùng cùng schema dữ liệu, khác nhau ở template.
- [x] Không thể Active feature không có provider.
- [x] Fallback cũ vẫn hoạt động và được ghi audit.
- [ ] Có test ngăn rò dữ liệu điểm ngoài class/unit được phép.

---

## Sprint 16 — Nghiệm thu LHL theo PDF chuẩn (P1)

### Mục tiêu

Chứng minh Word và Excel từ Template Engine khớp tối đa với `mẫu xuất LHL.pdf`, thay vì chỉ kiểm tra file được tạo.

### Công việc

| ID | Việc | Ưu tiên |
|---|---|---|
| S16-01 | Đưa Word/Excel chuẩn LHL vào bộ reference fixture có version | P0 |
| S16-02 | Thêm integration test Active Word; mở rộng test Active Excel nhiều tuần/nhiều lớp | P0 |
| S16-03 | Golden test cho header `Tuần → Ngày`, footer, chữ ký, border, merge, row/column size | P1 |
| S16-04 | Case bắt buộc Tuần 9 `(17-23)` không cắt/vỡ ô | P0 |
| S16-05 | Case grouping liên tiếp `1-3 TTT / 4-5 GPSL / 6-9 TTT` | P0 |
| S16-06 | Kiểm tra VHTT, NPL, SHL, NL, NT, NH như môn bình thường | P0 |
| S16-07 | Kiểm tra không tô màu nền môn học, trừ khi template chủ động cấu hình | P1 |
| S16-08 | UAT Word/Excel bằng dữ liệu thật; ghi checklist sai khác so với PDF | P0 |
| S16-09 | Sau một chu kỳ UAT ổn định mới lập quyết định tắt legacy fallback | P1 |

### Definition of Done

- [ ] Active Word và Active Excel đều có integration test.
- [x] Các case merge/grouping đặc biệt có regression test.
- [ ] Có checklist đối chiếu từng vùng với PDF chuẩn.
- [ ] Người dùng nghiệp vụ duyệt ít nhất một bản Word và một bản Excel.
- [ ] Chưa xóa exporter cũ; chỉ đánh dấu deprecation sau UAT.

---

## Sprint 17 — Dọn kiến trúc và nợ kỹ thuật (P1)

### Mục tiêu

Giảm code chết, controller/view quá lớn và các điểm lệch convention mà không đổi hành vi nghiệp vụ.

### Công việc

| ID | Việc | Ưu tiên |
|---|---|---|
| S17-01 | Xác minh rồi hợp nhất/xóa scaffold `TemplateManagement` trùng `ExportTemplates` | P0 |
| S17-02 | Xóa hoặc hoàn thiện `ExportTemplatesController`, model `ExportTemplates`, Update Request scaffold | P1 |
| S17-03 | Hoàn thiện rule cho các FormRequest còn TODO hoặc loại bỏ nếu route không dùng | P0 |
| S17-04 | Sửa 2 route name trùng `standard-hours.` | P1 |
| S17-05 | Tách `TrainingScheduleController` theo use case/service, giữ route contract cũ | P1 |
| S17-06 | Tách script lớn khỏi `course.blade.php`, `calendar.blade.php`, `import-modal.blade.php` | P1 |
| S17-07 | Chuẩn hóa controller mới theo `ModuleBaseController`/base controller phù hợp | P1 |
| S17-08 | Thêm Pint check và PHPStan/Larastan baseline; trước mắt chặn lỗi mới | P1 |
| S17-09 | Đồng bộ `TODO.md`, sprint docs và checklist review với code thực tế | P1 |

### Definition of Done

- [ ] Không còn module/controller/model scaffold trùng hoặc không được đăng ký.
- [x] Không còn FormRequest production với rule rỗng trong các request scaffold đã rà soát.
- [x] Route name không trùng.
- [ ] Các file lớn được tách theo chức năng, không làm đổi URL hoặc response.
- [ ] Static analysis không phát sinh lỗi mới ngoài baseline được ghi nhận.
- [ ] Toàn bộ test hiện có tiếp tục pass.

---

## Sprint 18 — UI regression, accessibility và E2E (P2)

### Mục tiêu

Kiểm tra các luồng quan trọng trên trình duyệt thật ở cả ba portal và ngăn UI quay lại native dialog.

### Công việc

| ID | Việc | Ưu tiên |
|---|---|---|
| S18-01 | Thêm static regression test cấm bare native `alert/confirm/prompt` | P1 |
| S18-02 | E2E Dashboard: đăng nhập, menu, form, popup xác nhận, session toast | P1 |
| S18-03 | E2E LMS: khóa học, điểm danh, chat, upload/nộp bài, popup LMS | P1 |
| S18-04 | E2E Grades: nhập/lưu/khóa/duyệt/xuất điểm, popup cam–teal | P1 |
| S18-05 | E2E Template: upload → preview → binding → active → export | P0 |
| S18-06 | Keyboard/focus trap/Escape/ARIA cho modal; kiểm tra screen reader label | P2 |
| S18-07 | Responsive smoke test desktop/tablet/mobile cho ba shell | P2 |
| S18-08 | Kiểm tra Turbo navigation không bind trùng event hoặc giữ stale popup | P1 |

### Definition of Done

- [ ] CI chạy E2E smoke cho ba portal.
- [x] Không có dialog native.
- [ ] Modal dùng được bằng bàn phím, focus trả về đúng trigger.
- [ ] Không nhân đôi toast/submit sau Turbo navigation.
- [ ] Luồng Template Engine end-to-end pass trên trình duyệt.

---

## Sprint 19 — Hiệu năng, vận hành và PDF tùy chọn (P2)

### Mục tiêu

Chuẩn bị vận hành dài hạn khi số template, lịch, điểm và file export tăng.

### Công việc

| ID | Việc | Ưu tiên |
|---|---|---|
| S19-01 | Benchmark preview/render với template lớn và nhiều lớp/học viên | P1 |
| S19-02 | Chuyển render nặng sang queue, có progress, timeout và retry an toàn | P1 |
| S19-03 | Thiết lập lifecycle cho file tạm, version đã archive và audit log | P1 |
| S19-04 | Metrics: thời gian render, tỉ lệ fallback, lỗi theo feature/version | P1 |
| S19-05 | Cảnh báo khi Active template lỗi liên tiếp hoặc fallback tăng bất thường | P1 |
| S19-06 | Implement `DocumentConverterInterface` bằng LibreOffice worker nếu nghiệp vụ chốt cần PDF | P2 |
| S19-07 | Sandbox/timeout/resource limit cho quá trình convert PDF | P1 nếu bật PDF |
| S19-08 | Backup/restore drill cho DB + template storage; runbook xử lý sự cố | P1 |
| S19-09 | Release checklist và quyết định deprecate exporter legacy theo từng feature | P1 |

### Definition of Done

- [ ] Render lớn không giữ request web quá timeout cho phép.
- [ ] File tạm được dọn tự động, không xóa version còn tham chiếu.
- [ ] Có dashboard/log phát hiện fallback và lỗi template.
- [ ] Nếu bật PDF: converter chạy tách biệt, có timeout và test parity.
- [ ] Backup DB và template storage phục hồi thử thành công.

---

## 3. Thứ tự phụ thuộc

```text
Sprint 14 — Security/release gate
        ↓
Sprint 15 — Template Engine dùng chung
        ↓
Sprint 16 — LHL visual/UAT
        ↓
Sprint 17 — Refactor sau khi hành vi ổn định
        ↓
Sprint 18 — Browser E2E và accessibility
        ↓
Sprint 19 — Performance/operations/PDF
```

Sprint 17 có thể chuẩn bị song song với Sprint 16 nhưng chỉ merge sau khi các golden test của Sprint 16 đã khóa hành vi.

## 4. Quality gate bắt buộc cuối mỗi sprint

```powershell
composer audit --locked --no-interaction
npm audit --omit=dev
php artisan view:cache
npm run build
php artisan test
```

Ngoài ra:

- Kiểm tra migration `up/down` trên database test.
- Kiểm tra permission cho Super Admin, Manager, Instructor và Student.
- Kiểm tra export legacy fallback nếu sprint có tác động Template Engine.
- Cập nhật tài liệu sprint và ghi rõ phần đã hoàn thành/chưa hoàn thành.

## 5. Thứ tự đề nghị triển khai

1. Bắt đầu ngay Sprint 14 vì có advisory bảo mật và pipeline deploy chưa có test gate.
2. Sprint 15 ưu tiên duy nhất `grades.score_sheet` trước; chỉ mở summary/transcript sau khi bảng điểm pass.
3. Sprint 16 không tắt fallback nếu chưa có UAT ký xác nhận.
4. Sprint 17 refactor từng lát nhỏ, không rewrite toàn module.
5. Sprint 19 chỉ làm PDF khi người dùng nghiệp vụ xác nhận đây là đầu ra bắt buộc.
