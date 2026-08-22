@props([
    'column',
    'label' => null,
    'defaultDirection' => 'asc',
])

@php
    $currentColumn = (string) request('sort_by', '');
    $currentDirection = strtolower((string) request('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
    $isActive = $currentColumn === (string) $column;
    $initialDirection = strtolower((string) $defaultDirection) === 'desc' ? 'desc' : 'asc';
    $nextDirection = $isActive
        ? ($currentDirection === 'asc' ? 'desc' : 'asc')
        : $initialDirection;
    $sortUrl = request()->fullUrlWithQuery([
        'sort_by' => $column,
        'sort_order' => $nextDirection,
        'page' => 1,
    ]);
@endphp

<th
    {{ $attributes->class('px-4 py-3 text-left font-semibold') }}
    aria-sort="{{ $isActive ? ($currentDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
>
    <a
        href="{{ $sortUrl }}"
        class="group inline-flex items-center gap-1.5 rounded-md text-slate-700 transition-colors hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
        title="Sắp xếp {{ $label ?? $slot }} {{ $nextDirection === 'asc' ? 'tăng dần' : 'giảm dần' }}"
    >
        <span>{{ $label ?? $slot }}</span>
        <span
            class="inline-flex h-5 w-5 items-center justify-center rounded text-[11px] {{ $isActive ? 'bg-blue-100 text-blue-700' : 'text-slate-400 group-hover:bg-blue-50 group-hover:text-blue-600' }}"
            aria-hidden="true"
        >
            <i class="bi {{ $isActive ? ($currentDirection === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down') : 'bi-arrow-down-up' }}"></i>
        </span>
    </a>
</th>
