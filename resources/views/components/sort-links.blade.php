@props([
    'baseUrl',
    'sorts',
    'currentOrder',
    'currentDirection',
])

@php
    $cleanUrl = strtok($baseUrl, '?');
@endphp
<ul class="pagination order">
@foreach($sorts as $label => $field)
    @if($currentOrder === $field)
        @php
            $newDir = $currentDirection === 'asc' ? 'desc' : 'asc';
            $arrow = $currentDirection === 'desc' ? '↓' : '↑';
        @endphp
        <li class="active"><a href="{{ $cleanUrl }}?order={{ $field }}&dir={{ $newDir }}">{{ $label }} {{ $arrow }}</a></li>
    @else
        <li><a href="{{ $cleanUrl }}?order={{ $field }}&dir={{ $currentDirection }}">{{ $label }}</a></li>
    @endif
@endforeach
</ul>
