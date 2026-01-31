@extends('layouts.application')

@section('content')
<div class="grid_12">

<h2>Glömt lösenord</h2>

<p>Om du har angett en e-postadress för ditt konto så är det bara att fylla i den eller ditt
    användarnamn nedan så kommer en länk för återställning av lösenordet skickas till dig.
    Mailet bör komma inom ett par minuter. Kontrollera också din spambox om mailet inte tycks anlända.</p>
<p>Länken är endast giltig i 48 timmar.</p>

<form method="post" action="/account/forgot">
    @csrf
    <p>
    <label>Användarnamn / E-post</label>
    <input type="text" name="username" class="text" value="{{ old('username') }}" />
    @error('username')
        <div class="error">{{ $message }}</div>
    @enderror
    </p>
    <br />
    <div class="buttons">
        <button type="submit" class="positive">
            <img src="/static/images/icons/tick.png" alt=""/>
            Skicka
        </button>
    </div>
</form>

</div>
@endsection
