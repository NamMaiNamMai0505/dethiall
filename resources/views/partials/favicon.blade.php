@php
    // Local brand icon (same asset as LMS nav logo)
    $favicon = asset('images/brand-logo.png');
@endphp
<link rel="icon" type="image/png" sizes="88x88" href="{{ $favicon }}?v=2">
<link rel="shortcut icon" type="image/png" href="{{ $favicon }}?v=2">
<link rel="apple-touch-icon" href="{{ $favicon }}?v=2">
