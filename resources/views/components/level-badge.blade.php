@props(['level' => '', 'text' => '', 'size' => 'sm'])

@php
    $levelColors = [
        'beginner' => 'bg-green-100 text-green-800',
        'intermediate' => 'bg-blue-100 text-blue-800',
        'advanced' => 'bg-orange-100 text-orange-800',
        'expert' => 'bg-purple-100 text-purple-800'
    ];

    $colorClass = $levelColors[$level] ?? 'bg-gray-100 text-gray-800';
    $sizeClass = $size === 'lg' ? 'px-3 py-2 text-sm' : 'px-2 py-1 text-xs';
@endphp

<span class="{{ $colorClass }} {{ $sizeClass }} font-medium rounded-full">
    {{ $text ?: $level }}
</span>
