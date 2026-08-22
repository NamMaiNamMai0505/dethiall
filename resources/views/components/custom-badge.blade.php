@props([
    'type' => 'default',
    'text' => '',
    'size' => 'sm',
    'color' => ''
])

@php
    $types = [
        'draft' => 'bg-gray-100 text-gray-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'success' => 'bg-green-100 text-green-800',
        'warning' => 'bg-yellow-100 text-yellow-800',
        'danger' => 'bg-red-100 text-red-800',
        'info' => 'bg-blue-100 text-blue-800',
        'default' => 'bg-gray-100 text-gray-800'
    ];

    $colorClass = $color ?: ($types[$type] ?? $types['default']);
    $sizeClass = $size === 'lg' ? 'px-3 py-2 text-sm' : 'px-2 py-1 text-xs';
@endphp

<span class="{{ $colorClass }} {{ $sizeClass }} font-medium rounded-full">
    {{ $text }}
</span>
