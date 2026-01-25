@props([
    'username',
    'currentPage',
    'totalPages',
    'perPage',
    'order',
    'direction',
])

@php
    $numLinks = 2; // Number of links before/after current page
    $numLinksEnd = 1; // Number of links at the very ends

    // Calculate start and end of middle digit links
    $start = max(1, $currentPage - $numLinks);
    $end = min($totalPages, $currentPage + $numLinks);

    // Adjust start if close to beginning
    if (($start - $numLinksEnd) <= 2) {
        $start = 1;
    }

    // Adjust end if close to ending
    if (($end + $numLinksEnd) >= $totalPages - 1) {
        $end = $totalPages;
    }

    $buildUrl = function($page) use ($username, $perPage, $order, $direction) {
        $offset = ($page - 1) * $perPage;
        return "/users/{$username}?offset={$offset}&order={$order}&dir={$direction}";
    };
@endphp

@if($totalPages > 1)
<ul class="pagination">
    {{-- Previous arrow --}}
    @if($currentPage > 1)
        <li class="previous"><a href="{{ $buildUrl($currentPage - 1) }}">&lsaquo;</a></li>
    @else
        <li class="previous off">&lsaquo;</li>
    @endif

    {{-- First pages with ellipsis if needed --}}
    @if($start > 1)
        @for($i = 1; $i <= $numLinksEnd; $i++)
            <li><a href="{{ $buildUrl($i) }}">{{ $i }}</a></li>
        @endfor
        <li class="off">...</li>
    @endif

    {{-- Middle page links --}}
    @for($page = $start; $page <= $end; $page++)
        @if($page == $currentPage)
            <li class="active">{{ $page }}</li>
        @else
            <li><a href="{{ $buildUrl($page) }}">{{ $page }}</a></li>
        @endif
    @endfor

    {{-- Last pages with ellipsis if needed --}}
    @if($end < $totalPages)
        <li class="off">...</li>
        @for($i = $totalPages - $numLinksEnd + 1; $i <= $totalPages; $i++)
            <li><a href="{{ $buildUrl($i) }}">{{ $i }}</a></li>
        @endfor
    @endif

    {{-- Next arrow --}}
    @if($currentPage < $totalPages)
        <li class="previous"><a href="{{ $buildUrl($currentPage + 1) }}">&rsaquo;</a></li>
    @else
        <li class="previous off">&rsaquo;</li>
    @endif
</ul>
@endif
