@if ($paginator->hasPages())
<div class="pagination">
    {{-- Previous Page Link --}}
    @if ($paginator->onFirstPage())
        <span class="off">&lsaquo;</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}">&lsaquo;</a>
    @endif

    {{-- Pagination Elements --}}
    @foreach ($elements as $element)
        {{-- "Three Dots" Separator --}}
        @if (is_string($element))
            <span class="off">{{ $element }}</span>
        @endif

        {{-- Array Of Links --}}
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next Page Link --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}">&rsaquo;</a>
    @else
        <span class="off">&rsaquo;</span>
    @endif
</div>
@endif
