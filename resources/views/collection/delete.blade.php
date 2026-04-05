@extends('layouts.application')

@section('content')
<form method="post" action="/collection/delete">
@csrf

<h2>Ta bort skiva</h2>

<p>Är du säker på att du vill ta bort {{ $record->name }} - {{ $record->title }}?</p>

<input type="hidden" name="record" value="{{ $record->id }}" />

<p>
<div class="buttons">
    <button type="submit" class="positive">
        <i class="fa-solid fa-trash" aria-hidden="true"></i>
        Ta bort
    </button>
</div>
<a href="/users/{{ Auth::user()->username }}">Gå tillbaka</a>
</p>
</form>
@endsection
