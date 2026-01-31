@extends('layouts.application')

@section('content')
<div class="grid_6">
@if($errors->any())
    @foreach($errors->all() as $error)
        <div class="error">{{ $error }}</div>
    @endforeach
@endif
<h2>{{ $id == 0 ? 'Ny' : 'Redigera' }} skiva</h2>
<form method="post" action="{{ $id > 0 ? '/collection/record/' . $id : '/collection/record' }}">
@csrf
<label>Artist/grupp</label>
@error('artist') <div class="error">{{ $message }}</div> @enderror
<input type="text" class="text" name="artist" maxlength="100" value="{{ old('artist', $record->name) }}" />
<span>Artisten eller gruppen som släppt skivan, t.ex. The Beatles, U2 eller Radiohead.
För samlingsskivor med blandade artister, använd gärna Various eller V/A. Max 64 tecken.</span>

<label>Titel</label>
@error('title') <div class="error">{{ $message }}</div> @enderror
<input type="text" class="text" name="title" maxlength="150" value="{{ old('title', $record->title) }}" />
<span>Skivans titel, t.ex. Abbey Road, The Joshua Tree eller Kid A. Max 150 tecken.</span>

<label>År</label>
@error('year') <div class="error">{{ $message }}</div> @enderror
<input type="text" class="text" name="year" maxlength="4" value="{{ old('year', $record->year) }}" />
<span>Året för originalrelease med fyra siffror, t.ex. 1999 eller 2010. Kan lämnas tomt.</span>

<label>Format</label>
@error('format') <div class="error">{{ $message }}</div> @enderror
<input type="text" class="text" name="format" maxlength="30" value="{{ old('format', $record->format) }}" />
<span>Formatet på skivan, t.ex. CD, 12" eller CD/DVD. Max 30 tecken. Kan lämnas tomt.</span>

@if($id == 0)
<label>Kommentar</label>
@error('comment') <div class="error">{{ $message }}</div> @enderror
<input type="text" class="text" name="comment" maxlength="255" value="{{ old('comment') }}" />
<span>Max 255 tecken.</span>
@endif

<br />
<div class="buttons">
    <button type="submit" class="positive">
        <img src="/static/images/icons/tick.png" alt=""/>
        {{ $id == 0 ? 'Lägg till' : 'Spara' }}
    </button>
</form>
@if($id > 0)
    <a href="/collection/delete/{{ $record->id }}" class="positive button">
        <img src="/static/images/icons/delete-icon.png" alt=""/>
        Ta bort skiva
    </a>
@endif
</div>

</div>
<div class="grid_6">
@if($id > 0)
<h2>Kommentera skiva</h2>

<p>Skriv en kommentar för {{ $record->name }} - {{ $record->title }}.</p>

<form method="post" action="/collection/comment">
    @csrf
    <label>Kommentar</label>
    <input type="text" class="text" name="comment" maxlength="255" value="{{ old('comment', $record->comment) }}" />
    <span>Max 255 tecken.</span>
    <br />

    <input type="hidden" name="record" value="{{ $record->id }}" />
    <input type="hidden" name="action" value="edit" />

    <div class="buttons">
        <button type="submit" class="positive">
            <img src="/static/images/icons/tick.png" alt=""/>
            Spara
        </button>
</form>
<form method="post" action="/collection/comment">
    @csrf
    <input type="hidden" name="record" value="{{ $record->id }}" />
    <input type="hidden" name="action" value="delete" />
    <button type="submit" class="positive">
        <img src="/static/images/icons/delete-icon.png" alt=""/>
        Ta bort kommentar
    </button>
</form>
    </div>
@endif

</div>
@endsection
