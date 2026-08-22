# Feature phụ thuộc template / file mẫu

**Ngày cập nhật:** 2026-07-19  
**Mục đích:** Biết feature nào **vỡ nếu mất/đổi file mẫu**, khác với UI Blade thông thường.

---

## Phân loại nhanh

| Mức | Ý nghĩa |
|-----|---------|
| **Hard** | Không có file → export/import fail hoặc output sai cấu trúc |
| **Soft** | Có fallback code-gen, nhưng lệch “mẫu hành chính” trường |
| **DB template** | Mẫu lưu DB (JSON/HTML), không file disk |
| **Config** | Tên/chữ ký/đường dẫn trong config |

---

## 1. Hard — file disk bắt buộc / gần bắt buộc

| Feature | Template / path | Code chính | Ghi chú |
|---------|-----------------|------------|---------|
| **Xuất Lịch huấn luyện Excel (LHL)** | `resources/templates/lhl/Lich_Huan_Luyen_template.xlsx` · config `lhl_export.template_xlsx` | `TrainingPlanFromTemplateExport` · `TrainingExportService` | Clone sheet + **ZIP restore drawing** (gạch chéo connector, media chữ ký). Đổi merge/ô template → lệch layout. |
| **Ảnh chữ ký LHL trên Excel** | `storage/app/public/signatures/lhl/*.png` (config `lhl_export.signers[].image`) | Cùng export LHL | Thiếu PNG → vẫn xuất, mất/ảnh trống vùng ký. |
| **Mẫu tham chiếu Word khoa (lịch theo lớp)** | Repo: `Mau_Xuat lich_Khoa_Theo lop.docx` | `exportFacultyPlan` / Word grid | Thường **tham chiếu layout** + `WordExportTemplate` code-gen; file gốc để đối chiếu. |
| **Mẫu Excel gốc HK2 (tham chiếu dev)** | `Lịch Huấn Luyện HK2 25-26.xlsx` / `Lich_Huan_Luyen_mẫu.xlsx` (repo root) | Không load runtime nếu đã copy vào `resources/templates` | Source of truth thiết kế; runtime dùng path `resources/templates/lhl/`. |

### Runtime resolve LHL (thứ tự)

1. `config('lhl_export.template_xlsx')`  
2. Fallback `resource_path('templates/lhl/Lich_Huan_Luyen_template.xlsx')`  

Nếu **cả hai missing** → export LHL Excel fail.

---

## 2. Soft — code-gen, template optional / programmatic

| Feature | “Template” | Mức | Ghi chú |
|---------|------------|-----|---------|
| **Xuất LHL Word A3 / grid** | `TrainingPlanWordExport` + PhpWord | Soft | Tự dựng OOXML/PNG slash; không đọc `.docx` mẫu cứng. |
| **Xuất kế hoạch khoa Word** | `WordExportTemplate` + rows | Soft | Bảng + heading; không phụ thuộc file Word nếu service code-gen. |
| **Xuất chi tiết lịch** | `ScheduleDetailsExport` / Word table | Soft | “Match template layout” bằng merge cell code. |
| **Lịch HV / Lịch GV Excel** | `StudentScheduleExport`, `InstructorScheduleExport` | Soft | PhpSpreadsheet build; comment “template” = layout code. |
| **Import user Excel** | `UsersTemplateExport` (download) | Soft-hard | File **sinh ra** từ code; user phải đúng cột sheet. Sai sheet → import fail. |
| **Import HV (student)** | Modal + sheet chuẩn | Soft-hard | Cột theo code import; “template” = file download từ app. |
| **Báo cáo giờ chuẩn export** | PhpSpreadsheet/Word layout trong StandardHours | Soft | `ReportDocumentLayout`, exports conversion/research. |
| **Chứng chỉ LMS in/show** | `lms_certificate_templates` HTML + `layout_json` | DB | Designer JSON (Sprint 9 M3); không file xlsx. |
| **Khảo sát template cross-course** | `lms_survey_templates` + questions | DB | Apply vào khóa (M6). |
| **Chữ ký số user** | `digital_signatures` + system templates seed | DB + file image | Seed 3 mẫu hệ thống; claim theo tên. Ảnh upload disk `public`. |

---

## 3. Config phụ thuộc “mẫu hành chính” (không phải file UI)

| Config | Ảnh hưởng |
|--------|-----------|
| `config/lhl_export.php` | Tiêu đề, kính gửi, note, **3 signers** (tên + match_names + image path) |
| `CAMPUS_RADIUS_M` | GPS P2 — không template file |
| `TRUSTED_PROXIES` | IP check-in — không template file |

Đổi tên lãnh đạo / ảnh ký → sửa config + file PNG, **claim chữ ký số** theo `match_names`.

---

## 4. SCORM / học liệu — “package template” kiểu khác

| Feature | Phụ thuộc | Ghi chú |
|---------|-----------|---------|
| SCORM player | Gói zip do GV upload (imsmanifest…) | Không có template trường; package hỏng → play/track fail |
| File học liệu | Upload path storage | Không template |

---

## 5. Checklist vận hành khi đổi mẫu

### Đổi LHL Excel
1. Backup `resources/templates/lhl/Lich_Huan_Luyen_template.xlsx`  
2. Giữ **merge, named regions, drawings connector, sheet structure** mà code đọc  
3. Test: xuất 1 khung lịch → mở Excel: gạch chéo + chữ ký + màu môn  
4. Nếu chỉ đổi logo/text tĩnh → ít rủi ro hơn đổi vùng lưới tiết  

### Đổi chữ ký
1. Cập nhật PNG under `storage/app/public/signatures/lhl/`  
2. Cập nhật `config/lhl_export.php` `signers`  
3. Chạy seed/claim chữ ký số nếu dùng module DigitalSignature  

### Đổi import user
1. Download template mới từ UI  
2. Không sửa tay header cột nếu không đổi code import  

---

## 6. Feature **không** phụ thuộc template file

- CRUD lớp/môn/lịch/GV  
- LMS phòng học, chat, forum, thi MCQ (trừ SCORM package)  
- Điểm danh IP/probe/GPS (config mạng, không file mẫu)  
- Dashboard thống kê  
- RBAC `permissions:sync`  

---

## 7. Tóm tắt rủi ro

| Ưu tiên bảo vệ | Item |
|----------------|------|
| 🔴 Cao | `Lich_Huan_Luyen_template.xlsx` + post-process ZIP drawing |
| 🟠 Trung | PNG chữ ký LHL + `lhl_export.php` signers |
| 🟡 | Import user/HV sheet columns |
| 🟢 | Word LHL/khoa (code-gen), cert/survey DB templates |
