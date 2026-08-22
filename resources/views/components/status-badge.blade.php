@props(['isActive' => true, 'withIcon' => true, 'size' => 'sm'])

@php
    $statusClass = $isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    $sizeClass = $size === 'lg' ? 'px-3 py-2 text-sm' : 'px-2 py-1 text-xs';
    $icon = $isActive ? 'check-circle' : 'pause-circle';
    $text = $isActive ? 'Đang hoạt động' : 'Tạm dừng';
@endphp

<span class="{{ $statusClass }} {{ $sizeClass }} font-medium rounded-full">
    @if($withIcon)
        <i class="bi bi-{{ $icon }} mr-1"></i>
    @endif
    {{ $text }}
</span>
