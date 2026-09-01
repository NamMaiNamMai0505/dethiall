<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f8fafc;color:#0f172a;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center}
        main{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:24px;max-width:520px;width:calc(100% - 32px);box-shadow:0 10px 28px rgba(15,23,42,.08)}
        h1{font-size:20px;margin:0 0 10px}
        p{font-size:14px;line-height:1.6;margin:0 0 16px;color:#475569}
        button{border:0;border-radius:6px;background:#0f766e;color:#fff;font-weight:700;padding:10px 14px;cursor:pointer}
    </style>
</head>
<body>
    <main>
        <h1>Đã gửi lệnh in</h1>
        <p>Giấy nghỉ phép #{{ $requestId }} đã được đổ dữ liệu vào mẫu Word đang dùng và gửi sang máy in mặc định của Windows.</p>
        <button type="button" onclick="window.close()">Đóng</button>
    </main>
</body>
</html>
