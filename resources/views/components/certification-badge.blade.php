@props(['type' => '', 'text' => '', 'size' => 'sm'])

@php
    $certColors = [
        'certificate' => 'bg-yellow-100 text-yellow-800',
        'secondary_diploma' => 'bg-emerald-100 text-emerald-800',
        'college_diploma' => 'bg-green-100 text-green-800',
        'bachelor_degree' => 'bg-blue-100 text-blue-800',
        'master_degree' => 'bg-indigo-100 text-indigo-800',
        'doctorate_degree' => 'bg-purple-100 text-purple-800'
    ];

    $colorClass = $certColors[$type] ?? 'bg-gray-100 text-gray-800';
    $sizeClass = $size === 'lg' ? 'px-3 py-2 text-sm' : 'px-2 py-1 text-xs';
@endphp

<span class="{{ $colorClass }} {{ $sizeClass }} font-medium rounded-full">
    {{ $text ?: $type }}
</span>
