@props([
    'username',
    'offset' => 0,
    'currentOrder',
    'currentDirection',
])

@php
    $sorts = [
        'Artist' => 'artist',
        'Format' => 'format',
        'År' => 'year',
    ];

    $buildUrl = function($field, $direction) use ($username, $offset) {
        return "/users/{$username}?offset={$offset}&order={$field}&dir={$direction}";
    };
@endphp

<ul class="pagination order">
@foreach($sorts as $label => $field)
    @if($currentOrder === $field)
        @php
            $newDir = $currentDirection === 'asc' ? 'desc' : 'asc';
            $arrow = $currentDirection === 'desc' ? '↓' : '↑';
        @endphp
        <li class="active"><a href="{{ $buildUrl($field, $newDir) }}">{{ $label }} {{ $arrow }}</a></li>
    @else
        <li><a href="{{ $buildUrl($field, $currentDirection) }}">{{ $label }}</a></li>
    @endif
@endforeach
</ul>
