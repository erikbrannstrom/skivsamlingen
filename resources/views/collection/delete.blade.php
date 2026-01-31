@extends('layouts.application')

@section('content')
<form method="post" action="/collection/delete">
@csrf

<div class="grid_12">

<h2>Ta bort skiva</h2>

<p>Är du säker på att du vill ta bort {{ $record->name }} - {{ $record->title }}?</p>

<input type="hidden" name="record" value="{{ $record->id }}" />

<p>
<div class="buttons">
    <button type="submit" class="positive">
        <img src="/static/images/icons/delete-icon.png" alt=""/>
        Ta bort
    </button>
</div>
<a href="/users/{{ Auth::user()->username }}">Gå tillbaka</a>
</p>

</div>
</form>
@endsection
