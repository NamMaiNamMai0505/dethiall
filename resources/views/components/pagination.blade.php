@props([
    'currentPage' => 1,
    'totalPages' => 1,
    'showPages' => 5,
    'baseUrl' => '',
    'queryParams' => []
])

@if($totalPages > 1)
@php
    $start = max(1, $currentPage - (int) floor($showPages / 2));
    $end = min($totalPages, $start + $showPages - 1);
    if ($end - $start + 1 < $showPages) {
        $start = max(1, $end - $showPages + 1);
    }
    $q = fn (int $page) => $baseUrl.'?'.http_build_query(array_merge($queryParams, ['page' => $page]));
@endphp
<nav class="ui-pagination" role="navigation" aria-label="Phân trang">
    <ul class="ui-pagination__list">
        <li>
            @if($currentPage > 1)
                <a href="{{ $q($currentPage - 1) }}" class="ui-pagination__page is-nav" aria-label="Trang trước">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </a>
            @else
                <span class="ui-pagination__page is-nav is-disabled" aria-disabled="true">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </span>
            @endif
        </li>

        @if($start > 1)
            <li><a href="{{ $q(1) }}" class="ui-pagination__page">1</a></li>
            @if($start > 2)
                <li><span class="ui-pagination__page is-ellipsis">…</span></li>
            @endif
        @endif

        @for($page = $start; $page <= $end; $page++)
            <li>
                @if($page == $currentPage)
                    <span class="ui-pagination__page is-active" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $q($page) }}" class="ui-pagination__page">{{ $page }}</a>
                @endif
            </li>
        @endfor

        @if($end < $totalPages)
            @if($end < $totalPages - 1)
                <li><span class="ui-pagination__page is-ellipsis">…</span></li>
            @endif
            <li><a href="{{ $q($totalPages) }}" class="ui-pagination__page">{{ $totalPages }}</a></li>
        @endif

        <li>
            @if($currentPage < $totalPages)
                <a href="{{ $q($currentPage + 1) }}" class="ui-pagination__page is-nav" aria-label="Trang sau">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            @else
                <span class="ui-pagination__page is-nav is-disabled" aria-disabled="true">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </span>
            @endif
        </li>
    </ul>
</nav>
@endif
