# Sprint plan — UI sync + Quản lý điểm + Mẫu xuất chung

**Ngày lập:** 2026-07-21  
**Quy tắc:** Admin đồng bộ admin · LMS đồng bộ LMS (không gộp 1 design system).  
**Giờ chuẩn:** gán Đối tượng/Chức danh **chỉ trên User** (không form Instructor).  
**Ngoài phạm vi:** SSO, video call, MAC thiết bị, proctor camera, role grader mịn.

---

## Sprint 1 — Wave A (dễ) ✅ target
- User edit = pattern create (page-header, form components, Chức danh + Đối tượng)
- Ẩn/dead-route hour-norms & research-norms views (redirect hub)
- Dropdown position chỉ active + giữ giá trị đang chọn

## Sprint 2 — Wave B
- Lịch GV: soft entry LMS là chính; soft link/note InstructorSchedule
- Navbar LMS → Giờ chuẩn: deep-link ổn

## Sprint 3 — Wave D (partial)
- Chuẩn hóa empty state / nút primary một số màn admin lệch nặng
- LMS partial consistency (btn classes)

## Sprint 4 — Wave C (không commit)
- PHPUnit CalculationService golden (380×10%=38)
- Không `git commit`

## Sprint 5 — Grades core
- Module `Grades` shell cam-teal
- Models: grade books, columns, cells, locks
- GV nhập điểm lớp mình (cột 15p / 1 tiết / tay)
- Scope: instructor / manager unit / super-admin

## Sprint 6 — Workflow duyệt
- Lock GV → chờ PDOT
- PDOT duyệt / yêu cầu mở
- Ý kiến lên Chủ nhiệm PDOT khi đã khóa cần sửa
- Audit log

## Sprint 7 — ExportTemplates (chung 3 hệ)
- Module/shared: templates CRUD, scope dashboard|lms|grades
- Upload xlsx/docx, map feature key
- “AI light”: scan cells → gợi ý placeholders {{...}}

## Sprint 8 — Grades export
- Xuất điểm theo mẫu đã map

---

## Backlog lỗi theo sprint
| Sprint | Issue | Status |
|--------|-------|--------|
| | | |
