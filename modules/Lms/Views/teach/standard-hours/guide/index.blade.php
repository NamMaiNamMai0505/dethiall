@extends('layouts.lms-learner')

@section('title', 'Hướng dẫn Giờ chuẩn giảng viên')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-6 py-5 text-white">
            <h1 class="text-lg font-bold"><i class="bi bi-journal-check mr-2"></i>Hướng dẫn sử dụng Giờ chuẩn</h1>
            <p class="mt-1 text-sm text-teal-50">Các bước kê khai hoạt động chuyên môn, NCKH và bảng giờ chuẩn hằng năm.</p>
        </div>
        <div class="p-5">
            <a href="{{ route('lms.teach.standard-hours.hub') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:text-teal-800">
                <i class="bi bi-arrow-left"></i> Về trang Giờ chuẩn
            </a>
        </div>
    </div>

    <div class="lms-card rounded-2xl p-5">
        <div class="rounded-xl border border-teal-100 bg-teal-50 p-4 text-sm text-teal-900">
            <i class="bi bi-magic mr-1"></i>
            Mỗi tiết có tên bạn trong Lịch huấn luyện được tự động tính vào <strong>Trực tiếp giảng dạy</strong>.
            Bạn không cần kê khai lại tiết dạy hay điểm danh cá nhân.
        </div>

        <div class="mt-6 space-y-6">
            <div>
                <h3 class="text-sm font-bold text-slate-900">1. Kê khai hoạt động chuyên môn</h3>
                <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-slate-600">
                    <li>Mở <a class="font-medium text-teal-700 underline" href="{{ route('standard-hours.conversion-records.index') }}" data-turbo="false">Kê khai HĐ chuyên môn</a> và chọn <strong>Thêm kê khai</strong>.</li>
                    <li>Chọn danh mục hoạt động phù hợp — các tiết giảng trực tiếp không xuất hiện vì đã lấy từ lịch.</li>
                    <li>Nhập chi tiết hoạt động, ngày thực hiện, số lượng; kiểm tra giờ quy đổi realtime.</li>
                    <li>Đính kèm minh chứng và lưu nháp hoặc gửi thẩm định.</li>
                </ol>
            </div>

            <div class="border-t border-slate-100 pt-6">
                <h3 class="text-sm font-bold text-slate-900">2. Kê khai nghiên cứu khoa học</h3>
                <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-slate-600">
                    <li>Mở <a class="font-medium text-teal-700 underline" href="{{ route('standard-hours.research-records.index') }}" data-turbo="false">Kê khai NCKH</a>, chọn sản phẩm và nhập ngày nghiệm thu.</li>
                    <li>Nhập tổng số thành viên, số năm thực hiện và vai trò của bạn (Chủ nhiệm/Thành viên).</li>
                    <li>Hệ thống tự tính giờ theo Luật quy đổi, vẫn có thể điều chỉnh số giờ kê khai.</li>
                    <li>Đính kèm minh chứng rồi lưu nháp hoặc gửi duyệt.</li>
                </ol>
            </div>

            <div class="border-t border-slate-100 pt-6">
                <h3 class="text-sm font-bold text-slate-900">3. Kê khai hoạt động ngoài HĐCM</h3>
                <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-slate-600">
                    <li>Mở <a class="font-medium text-teal-700 underline" href="{{ route('standard-hours.external-activities.index') }}" data-turbo="false">Hoạt động ngoài HĐCM</a> và chọn <strong>Thêm kê khai</strong>.</li>
                    <li>Chọn nhóm hoạt động, nhập thời gian, vai trò, đơn vị tổ chức, kết quả và minh chứng.</li>
                    <li>Dữ liệu này được theo dõi riêng, không tự động cộng vào giờ HĐCM hoặc NCKH.</li>
                </ol>
            </div>

            <div class="border-t border-slate-100 pt-6">
                <h3 class="text-sm font-bold text-slate-900">4. Lập bảng kê khai giờ chuẩn hằng năm</h3>
                <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-slate-600">
                    <li>Mở <a class="font-medium text-teal-700 underline" href="{{ route('lms.teach.standard-hours.my-results.index') }}">Kê khai giờ chuẩn</a> và chọn <strong>Tạo/Cập nhật kê khai</strong>.</li>
                    <li>Kiểm tra thông tin, chọn khoảng ngày trong cùng một năm, Đối tượng và Chức danh.</li>
                    <li>Chọn <strong>Truy xuất số giờ giảng từ Lịch huấn luyện</strong> để xem tổng tiết và chi tiết theo ngày/môn/lớp.</li>
                    <li>Nếu có giờ giảng hợp lệ ngoài lịch, nhập vào <strong>Giờ giảng dạy khác</strong> kèm giải trình.</li>
                    <li>Kiểm tra tổng giờ rồi lưu bảng kê khai năm.</li>
                </ol>
            </div>

            <div class="border-t border-slate-100 pt-6">
                <h3 class="text-sm font-bold text-slate-900">5. Theo dõi hồ sơ và kết quả</h3>
                <ul class="mt-2 list-disc space-y-1.5 pl-5 text-sm text-slate-600">
                    <li><strong>Nháp:</strong> được sửa hoặc xóa.</li>
                    <li><strong>Chờ thẩm định:</strong> hồ sơ đã gửi, chưa tham gia phép tính.</li>
                    <li><strong>Đã thẩm định:</strong> được cộng vào kết quả năm.</li>
                    <li><strong>Cần bổ sung:</strong> cập nhật nội dung/minh chứng rồi gửi lại.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="lms-card rounded-2xl p-5 bg-slate-900 text-white">
        <h2 class="text-base font-bold"><i class="bi bi-calculator mr-2"></i>Công thức dữ liệu liên kết</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div><strong>Trực tiếp giảng dạy</strong><p class="mt-1 text-sm text-slate-300">Số tiết được gán GV trên lịch + giờ giảng khác trong khoảng kê khai.</p></div>
            <div><strong>HĐ chuyên môn</strong><p class="mt-1 text-sm text-slate-300">Tổng kê khai đã duyệt ± giờ quy đổi.</p></div>
            <div><strong>Tổng giờ chuẩn</strong><p class="mt-1 text-sm text-slate-300">Trực tiếp giảng dạy + HĐ chuyên môn.</p></div>
            <div><strong>Kết luận</strong><p class="mt-1 text-sm text-slate-300">Đủ tổng giờ, tỷ lệ giảng dạy và định mức NCKH.</p></div>
        </div>
    </div>
</div>
@endsection
