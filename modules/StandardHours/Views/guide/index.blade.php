@extends('layouts.admin')

@section('title', 'Hướng dẫn Giờ chuẩn giảng viên')
@section('page-title', 'Hướng dẫn Giờ chuẩn giảng viên')

@push('styles')
<style>
    .sh-guide { color: #0f172a; }
    .sh-guide * { box-sizing: border-box; }
    .sh-guide-hero { background: #fff; border: 1px solid #cbd5e1; border-left: 6px solid #0f4c81; border-radius: 16px; padding: 24px; }
    .sh-guide-kicker { color: #075985; font-size: 12px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
    .sh-guide-title { color: #0f172a; font-size: 25px; font-weight: 800; line-height: 1.3; margin-top: 6px; }
    .sh-guide-copy { color: #475569; font-size: 14px; line-height: 1.65; }
    .sh-guide-login { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px 14px; color: #0f172a; }
    .sh-guide-grid { display: grid; gap: 18px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .sh-guide-card { background: #fff; border: 1px solid #cbd5e1; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 22px rgba(15, 23, 42, .05); }
    .sh-guide-card-head { background: #eaf2f8; border-bottom: 1px solid #b8cde0; padding: 18px 22px; color: #102a43; }
    .sh-guide-card-head.manager { background: #f3eefb; border-color: #d7c8ed; color: #38245d; }
    .sh-guide-card-body { padding: 22px; color: #334155; }
    .sh-guide-section + .sh-guide-section { border-top: 1px solid #e2e8f0; margin-top: 20px; padding-top: 20px; }
    .sh-guide-section h3 { color: #0f172a; font-size: 16px; font-weight: 800; margin-bottom: 9px; }
    .sh-guide-section ol, .sh-guide-section ul { margin: 0; padding-left: 21px; }
    .sh-guide-section li { margin: 7px 0; line-height: 1.55; }
    .sh-guide-section a { color: #075985; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
    .sh-guide-note { background: #fff8e6; border: 1px solid #e9c46a; border-radius: 12px; color: #5f3b00; padding: 14px 16px; line-height: 1.55; }
    .sh-guide-auto { background: #ecfdf5; border: 1px solid #86d8b0; border-radius: 14px; color: #123d2a; padding: 18px; }
    .sh-guide-formula { background: #0f172a; border-radius: 16px; color: #fff; padding: 22px; }
    .sh-guide-formula p, .sh-guide-formula li { color: #e2e8f0; }
    .sh-guide-pill { display: inline-flex; align-items: center; border: 1px solid #94a3b8; border-radius: 999px; background: #fff; color: #1e293b; font-size: 12px; font-weight: 700; padding: 5px 9px; }
    @media (max-width: 980px) { .sh-guide-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Hướng dẫn sử dụng']
]" />

<x-page-header title="HƯỚNG DẪN SỬ DỤNG GIỜ CHUẨN" :actions="[[
    'url' => route('standard-hours.hub'), 'label' => 'Về trang Giờ chuẩn', 'icon' => 'arrow-left', 'color' => 'gray'
]]" />

<div class="sh-guide space-y-6">
    <section class="sh-guide-hero">
        <div class="grid gap-5 lg:grid-cols-[1fr_360px]">
            <div>
                <p class="sh-guide-kicker">Bộ demo hoàn chỉnh · Năm 2026</p>
                <h1 class="sh-guide-title">Nguyễn Văn A (GV-0001) · Khoa Điều dưỡng</h1>
                <p class="sh-guide-copy mt-2">
                    Dữ liệu demo có hoạt động chuyên môn, NCKH, minh chứng, giảm trừ, quy đổi và kết quả tính.
                    Giờ trực tiếp giảng dạy được lấy tự động từ lịch đã phân công.
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="sh-guide-pill"><i class="bi bi-calendar-check mr-1"></i>{{ app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel() }}</span>
                    <span class="sh-guide-pill"><i class="bi bi-link-45deg mr-1"></i>Liên kết lịch giảng dạy</span>
                    <span class="sh-guide-pill"><i class="bi bi-shield-check mr-1"></i>Duyệt theo đơn vị</span>
                </div>
            </div>
            <div class="space-y-2">
                <div class="sh-guide-login">
                    <div class="text-xs font-bold uppercase text-slate-500">Tài khoản giảng viên</div>
                    <strong>giangvien@example.com</strong>
                </div>
                <div class="sh-guide-login">
                    <div class="text-xs font-bold uppercase text-slate-500">Tài khoản người duyệt</div>
                    <strong>quanlykhoa@example.com</strong>
                </div>
                <div class="sh-guide-note"><strong>Mật khẩu demo:</strong> password</div>
            </div>
        </div>
    </section>

    <section class="sh-guide-auto">
        <h2 class="text-lg font-extrabold"><i class="bi bi-magic mr-2"></i>Phần giảng viên không phải kê khai</h2>
        <p class="mt-2 text-sm leading-6">
            Mỗi tiết có giảng viên trong Lịch huấn luyện được tính vào <strong>Trực tiếp giảng dạy</strong> theo ngày của năm.
            Khi Phòng Đào tạo hoặc Khoa đổi giảng viên trên lịch, kết quả lần tính tiếp theo cập nhật cho đúng người.
            Giảng viên không kê khai lại tiết dạy và không phải điểm danh cá nhân.
        </p>
    </section>

    <div class="sh-guide-grid">
        <section class="sh-guide-card">
            <header class="sh-guide-card-head">
                <div class="text-xs font-extrabold uppercase tracking-wider">Vai trò 1</div>
                <h2 class="mt-1 text-xl font-extrabold"><i class="bi bi-person-workspace mr-2"></i>Giảng viên</h2>
            </header>
            <div class="sh-guide-card-body">
                <div class="sh-guide-section">
                    <h3>1. Kê khai hoạt động chuyên môn</h3>
                    <ol>
                        <li>Mở <a href="{{ route('standard-hours.conversion-records.index') }}">Kê khai HĐ chuyên môn</a> và chọn <strong>Thêm kê khai</strong>.</li>
                        <li>Ở <strong>Tên hoạt động chuyên môn</strong>, chọn danh mục phù hợp. Các mục tiết giảng trực tiếp không xuất hiện vì đã lấy từ lịch.</li>
                        <li>Nhập <strong>Chi tiết hoạt động</strong>, ngày thực hiện và số lượng. Năm được đồng bộ theo ngày.</li>
                        <li>Kiểm tra giờ quy đổi realtime, đính kèm minh chứng và lưu nháp hoặc gửi thẩm định.</li>
                    </ol>
                </div>

                <div class="sh-guide-section">
                    <h3>2. Kê khai nghiên cứu khoa học</h3>
                    <ol>
                        <li>Mở <a href="{{ route('standard-hours.research-records.index') }}">Kê khai NCKH</a>, chọn sản phẩm và nhập ngày nghiệm thu.</li>
                        <li>Nhập tổng số thành viên, số năm thực hiện và chọn vai trò của chính bạn (Chủ nhiệm/Thành viên).</li>
                        <li>Nếu là Thành viên, có thể nhập tỷ lệ đóng góp thực tế. Hệ thống tự tính giờ theo Luật quy đổi nhưng vẫn cho phép điều chỉnh số giờ kê khai.</li>
                        <li>Đính kèm minh chứng rồi lưu nháp hoặc gửi cấp trên quản lý duyệt.</li>
                    </ol>
                </div>

                <div class="sh-guide-section">
                    <h3>3. Kê khai hoạt động ngoài HĐCM</h3>
                    <ol>
                        <li>Mở <a href="{{ route('standard-hours.external-activities.index') }}">Hoạt động ngoài HĐCM</a> và chọn <strong>Thêm kê khai</strong>.</li>
                        <li>Chọn nhóm hoạt động, nhập thời gian, vai trò, đơn vị tổ chức, kết quả và minh chứng.</li>
                        <li>Lưu bản nháp, kiểm tra lại rồi gửi cấp trên hoặc cán bộ Khảo thí duyệt.</li>
                        <li>Dữ liệu này được theo dõi riêng, không tự động cộng vào giờ HĐCM hoặc NCKH.</li>
                    </ol>
                </div>

                <div class="sh-guide-section">
                    <h3>4. Lập bảng kê khai giờ chuẩn hằng năm</h3>
                    <ol>
                        <li>Mở <a href="{{ route('standard-hours.my-results.index') }}">Kê khai giờ chuẩn</a> và chọn <strong>Tạo/Cập nhật kê khai</strong>.</li>
                        <li>Kiểm tra mã số, họ tên; chọn khoảng ngày trong cùng một năm, Đối tượng và Chức danh. Định mức tự tính theo công thức giờ cơ sở × tỷ lệ chức danh.</li>
                        <li>Chọn <strong>Truy xuất số giờ giảng từ Lịch huấn luyện</strong> để xem tổng tiết và bảng chi tiết theo ngày, môn, lớp, phòng học.</li>
                        <li>Nếu có giờ giảng hợp lệ ngoài lịch, nhập vào <strong>Giờ giảng dạy khác</strong> và ghi rõ nội dung.</li>
                        <li>Kiểm tra tổng giờ trực tiếp, giờ HĐCM quy đổi, NCKH rồi lưu bảng kê khai năm.</li>
                    </ol>
                </div>

                <div class="sh-guide-section">
                    <h3>5. Theo dõi hồ sơ và kết quả</h3>
                    <ul>
                        <li><strong>Nháp:</strong> được sửa hoặc xóa.</li>
                        <li><strong>Chờ thẩm định:</strong> hồ sơ đã gửi, chưa tham gia phép tính.</li>
                        <li><strong>Đã thẩm định:</strong> được cộng vào kết quả năm.</li>
                        <li><strong>Cần bổ sung:</strong> cập nhật nội dung/minh chứng rồi gửi lại.</li>
                        <li>Xem sổ tổng hợp tại <a href="{{ route('standard-hours.my-results.index') }}">Kê khai giờ chuẩn</a>.</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="sh-guide-card">
            <header class="sh-guide-card-head manager">
                <div class="text-xs font-extrabold uppercase tracking-wider">Vai trò 2</div>
                <h2 class="mt-1 text-xl font-extrabold"><i class="bi bi-person-check mr-2"></i>Cấp trên quản lý</h2>
            </header>
            <div class="sh-guide-card-body">
                <div class="sh-guide-section">
                    <h3>1. Kiểm tra và phê duyệt</h3>
                    <ol>
                        <li>Đăng nhập tài khoản Manager; hệ thống giới hạn giảng viên theo khoa/đơn vị được phân công.</li>
                        <li>Mở danh sách <a href="{{ route('standard-hours.conversion-records.index', ['status' => 'submitted']) }}">HĐ CM chờ thẩm định</a>, <a href="{{ route('standard-hours.research-records.index', ['status' => 'submitted']) }}">NCKH chờ thẩm định</a> và <a href="{{ route('standard-hours.external-activities.index', ['status' => 'submitted']) }}">hoạt động ngoài HĐCM chờ duyệt</a>.</li>
                        <li>Đối chiếu chi tiết, số lượng, thành viên, giờ quy đổi và minh chứng; sau đó duyệt hoặc từ chối.</li>
                    </ol>
                </div>

                <div class="sh-guide-section">
                    <h3>2. Chuẩn bị dữ liệu tính</h3>
                    <ul>
                        <li>Gán Đối tượng và Chức danh giờ chuẩn cho tài khoản giảng viên.</li>
                        <li>Kiểm tra lịch đã gán đúng giảng viên; đây là nguồn giờ trực tiếp giảng dạy.</li>
                        <li>Ghi nhận giảm trừ, quy đổi giờ hoặc bộ môn nếu có.</li>
                        <li>Xử lý hết hồ sơ chờ thẩm định trước khi khóa kết quả.</li>
                    </ul>
                </div>

                <div class="sh-guide-section">
                    <h3>3. Tính và khóa theo năm</h3>
                    <ol>
                        <li>Mở <a href="{{ route('standard-hours.calculations.index', ['year' => 2026]) }}">Tính giờ chuẩn</a>, chọn năm <strong>2026</strong> và đơn vị.</li>
                        <li>Chọn <strong>Xem trước</strong> để phát hiện giảng viên thiếu cấu hình.</li>
                        <li>Chọn <strong>Tính giờ</strong>; hệ thống giữ Đối tượng, Chức danh và giờ giảng khác mà giảng viên đã kê khai, đồng thời truy xuất lại lịch mới nhất.</li>
                        <li>Báo cáo cuối đọc ba nguồn: bảng giờ chuẩn hằng năm, bảng HĐCM và bảng NCKH; đối chiếu xong mới <strong>Khóa</strong>.</li>
                        <li>Sau khi khóa, không thể tính lại cho đến khi có quy trình mở khóa phù hợp.</li>
                    </ol>
                </div>
            </div>
        </section>
    </div>

    <section class="sh-guide-formula">
        <h2 class="text-lg font-extrabold"><i class="bi bi-calculator mr-2"></i>Công thức dữ liệu liên kết</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div><strong>Trực tiếp giảng dạy</strong><p class="mt-1 text-sm">Số tiết được gán GV trên lịch + giờ giảng khác trong khoảng kê khai.</p></div>
            <div><strong>HĐ chuyên môn</strong><p class="mt-1 text-sm">Tổng kê khai đã duyệt ± giờ quy đổi.</p></div>
            <div><strong>Tổng giờ chuẩn</strong><p class="mt-1 text-sm">Trực tiếp giảng dạy + HĐ chuyên môn.</p></div>
            <div><strong>Kết luận</strong><p class="mt-1 text-sm">Đủ tổng giờ, tỷ lệ giảng dạy và định mức NCKH.</p></div>
        </div>
    </section>
</div>
@endsection
