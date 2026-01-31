@extends('layouts.application')

@section('content')
<div class="grid_12">

<form method="post" action="/account/recover/{{ $username }}/{{ $hash }}">
    @csrf

<h2>Välj nytt lösenord</h2>
<p>
    Fyll i ditt nya lösenord nedan. Därefter kommer du skickas vidare till inloggningen.
</p>

<p>
@error('password')
    <div class="error">{{ $message }}</div>
@enderror
<label>Nytt lösenord</label> <input type="password" name="password" maxlength="50" class="text" />
@error('password_confirmation')
    <div class="error">{{ $message }}</div>
@enderror
<label>Upprepa lösenord</label> <input type="password" name="password_confirmation" maxlength="50" class="text" />
</p>
<br />
<input type="submit" value="Ändra" />
</form>

</div>
@endsection
