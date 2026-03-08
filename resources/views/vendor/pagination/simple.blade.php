@if ($paginator->hasPages())
<div class="pagination">
    {{-- Previous Page Link --}}
    @if ($paginator->onFirstPage())
        <span class="off">&lsaquo;</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}">&lsaquo;</a>
    @endif

    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $from = max(1, $current - 2);
        $to = min($last, $current + 2);
    @endphp

    {{-- First page + dots --}}
    @if ($from > 1)
        <a href="{{ $paginator->url(1) }}">1</a>
        @if ($from > 2)
            <span class="off">...</span>
        @endif
    @endif

    {{-- Window around current page --}}
    @for ($page = $from; $page <= $to; $page++)
        @if ($page == $current)
            <span class="active">{{ $page }}</span>
        @else
            <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
        @endif
    @endfor

    {{-- Dots + last page --}}
    @if ($to < $last)
        @if ($to < $last - 1)
            <span class="off">...</span>
        @endif
        <a href="{{ $paginator->url($last) }}">{{ $last }}</a>
    @endif

    {{-- Next Page Link --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}">&rsaquo;</a>
    @else
        <span class="off">&rsaquo;</span>
    @endif
</div>
@endif
