# LHL Golden/UAT Checklist

Bộ checklist này dùng để đối chiếu bản Word/Excel render từ template Active với file PDF chuẩn `mẫu xuất LHL.pdf`.

## Header và footer

- [ ] Header hiển thị đúng thứ tự `Tuần` → `Ngày`.
- [ ] Footer/chữ ký giữ đúng vị trí, font và khoảng cách.
- [ ] Không có nền màu môn học; chỉ hiển thị tên viết tắt nếu template không cấu hình màu.

## Bảng lịch

- [ ] Tuần 9 hiển thị đầy đủ khoảng `(17-23)`, không cắt ô hoặc tràn layout.
- [ ] Nhóm liên tiếp `1-3 TTT`, `4-5 GPSL`, `6-9 TTT` được merge đúng.
- [ ] Các cột môn, giảng viên, địa điểm, nội dung và ghi chú merge theo cùng nhóm.
- [ ] Môn đặc biệt `VHTT`, `NPL`, `SHL`, `NL`, `NT`, `NH` hiển thị như môn bình thường.
- [ ] Kiểm tra nhiều tuần, nhiều lớp và trường hợp không có tiết.

## Ký duyệt và xuất file

- [ ] Word Active render đúng dữ liệu thật.
- [ ] Excel Active render đúng dữ liệu thật.
- [ ] Preview mock và file export dùng cùng binding.
- [ ] Fallback legacy được giữ cho tới khi người dùng nghiệp vụ ký duyệt.

Người duyệt: ____________________    Ngày: ____/____/______
