{{ $appName }}
========================

Xin chào {{ $recipientName }},

[{{ $typeLabel }}] {{ $notification->title }}

{{ $notification->message }}

@if(!empty($actionUrl))
Xem chi tiết: {{ $actionUrl }}
@endif

Thời gian: {{ optional($notification->created_at)->timezone(config('app.timezone'))->format('H:i d/m/Y') ?? now()->format('H:i d/m/Y') }}
Phân hệ: {{ $notification->module }}

Nội dung này cũng hiển thị trong chuông thông báo trên website.

---
Hệ thống Quản lý đào tạo – CDHC2
{{ $appUrl }}
Email được gửi tự động. Vui lòng không trả lời email này.
