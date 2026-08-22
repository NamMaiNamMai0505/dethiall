@props([
    'title' => '',
    'subtitle' => '',
    'actions' => []
])

@php
    // Full class strings — Tailwind JIT không build được bg-{{ $var }}-600
    $colorMap = [
        'blue' => 'bg-blue-600 hover:bg-blue-700 text-white border border-transparent',
        'green' => 'bg-green-600 hover:bg-green-700 text-white border border-transparent',
        'red' => 'bg-red-600 hover:bg-red-700 text-white border border-transparent',
        'amber' => 'bg-amber-500 hover:bg-amber-600 text-white border border-transparent',
        'yellow' => 'bg-amber-500 hover:bg-amber-600 text-white border border-transparent',
        'purple' => 'bg-purple-600 hover:bg-purple-700 text-white border border-transparent',
        'indigo' => 'bg-indigo-600 hover:bg-indigo-700 text-white border border-transparent',
        'teal' => 'bg-teal-600 hover:bg-teal-700 text-white border border-transparent',
        'slate' => 'bg-slate-700 hover:bg-slate-800 text-white border border-transparent',
        'gray' => 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 shadow-sm',
        'secondary' => 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 shadow-sm',
    ];
    $btnBase = 'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/30 whitespace-nowrap';
@endphp

<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-gray-600 mt-1 text-sm sm:text-base">{{ $subtitle }}</p>
        @endif
    </div>

    @if(count($actions) > 0)
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            @foreach($actions as $action)
                @php
                    $color = $action['color'] ?? 'blue';
                    $colorClass = $colorMap[$color] ?? $colorMap['blue'];
                    $classes = $btnBase.' '.$colorClass.(!empty($action['class']) ? ' '.$action['class'] : '');
                @endphp
                @if(isset($action['type']) && $action['type'] === 'form')
                    <form method="{{ $action['method'] ?? 'POST' }}"
                          action="{{ $action['url'] }}"
                          class="inline"
                          @if(isset($action['confirm']))
                              data-confirm="{{ $action['confirm'] }}"
                              @if(!empty($action['confirm_danger'])) data-confirm-danger="1" @endif
                          @endif>
                        @csrf
                        @if(isset($action['method']) && strtoupper($action['method']) !== 'POST')
                            @method($action['method'])
                        @endif
                        <button type="submit" class="{{ $classes }}">
                            @if(isset($action['icon']))
                                <i class="bi bi-{{ $action['icon'] }} leading-none"></i>
                            @endif
                            <span>{{ $action['label'] }}</span>
                        </button>
                    </form>
                @else
                    <a href="{{ $action['url'] }}" class="{{ $classes }}">
                        @if(isset($action['icon']))
                            <i class="bi bi-{{ $action['icon'] }} leading-none"></i>
                        @endif
                        <span>{{ $action['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    @endif
</div>
