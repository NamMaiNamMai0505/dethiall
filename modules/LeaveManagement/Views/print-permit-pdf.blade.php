<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        html, body { margin: 0; width: 100%; height: 100%; background: #d1d5db; }
        iframe { display: block; width: 100vw; height: 100vh; border: 0; }
        .fallback { position: fixed; right: 16px; bottom: 16px; z-index: 2; font-family: Arial, sans-serif; }
        .fallback button { border: 0; border-radius: 6px; background: #0f766e; color: #fff; cursor: pointer; font-weight: 700; padding: 10px 16px; }
        @media print { .fallback { display: none; } }
    </style>
</head>
<body>
<iframe id="permit-pdf" title="{{ $title }}"></iframe>
<div class="fallback"><button type="button" onclick="printPermit()">In giấy nghỉ phép</button></div>
<script>
const pdfData = atob(@json($pdfBase64));
const bytes = new Uint8Array(pdfData.length);
for (let i = 0; i < pdfData.length; i++) bytes[i] = pdfData.charCodeAt(i);
const pdfUrl = URL.createObjectURL(new Blob([bytes], { type: 'application/pdf' }));
const frame = document.getElementById('permit-pdf');
function printPermit() {
    try {
        frame.contentWindow.focus();
        frame.contentWindow.print();
    } catch (e) {
        window.print();
    }
}
frame.addEventListener('load', function () {
    setTimeout(printPermit, 600);
});
frame.src = pdfUrl;
</script>
</body>
</html>
