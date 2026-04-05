@extends('layouts.application')

@section('content')
<h2>Sökresultat</h2>
Din sökning efter <strong>{{ $query }}</strong> fick {{ count($users) }} {{ count($users) == 1 ? 'träff' : 'träffar' }}. En sökning ger maximalt 20 resultat.
@if(count($users) > 0)
<table class="search">
<tr>
    <th>&nbsp;</th>
    <th>Användarnamn</th>
    <th>Namn</th>
    <th>Antal skivor</th>
</tr>
@foreach($users as $user)
<tr>
    <td>
        @if($user->sex == 'm')
            <i class="fa-solid fa-mars" title="Man"></i>
        @elseif($user->sex == 'f')
            <i class="fa-solid fa-venus" title="Kvinna"></i>
        @endif
    </td>
    <td><a href="/users/{{ $user->username }}">{{ $user->username }}</a></td>
    <td>{{ $user->name }}</td>
    <td>{{ $user->num_records }} {{ $user->num_records == 1 ? 'skiva' : 'skivor' }}</td>
</tr>
@endforeach
</table>
@endif
@endsection
