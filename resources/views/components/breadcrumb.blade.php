@props(['items' => []])

@php
    // Class component đã normalize; fallback nếu gọi anonymous
    $crumbs = $items ?? [];
@endphp

<nav aria-label="Breadcrumb" class="text-sm text-gray-600 mb-4">
    <ol class="flex flex-wrap items-center gap-y-1">
        @foreach($crumbs as $index => $item)
            @php
                $title = $item['title'] ?? '';
                $url = $item['url'] ?? null;
                $isLast = $loop->last;
            @endphp

            <li class="inline-flex items-center">
                @if($index > 0)
                    <span class="mx-2 text-gray-400 select-none" aria-hidden="true">/</span>
                @endif

                @if($url)
                    <a href="{{ $url }}"
                       @if($isLast) aria-current="page" @endif
                       class="{{ $isLast
                            ? 'text-gray-900 font-medium hover:text-blue-700'
                            : 'text-blue-600 hover:text-blue-800' }} transition-colors">
                        {{ $title }}
                    </a>
                @else
                    <span class="{{ $isLast ? 'text-gray-900 font-medium' : 'text-gray-600' }}">
                        {{ $title }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
