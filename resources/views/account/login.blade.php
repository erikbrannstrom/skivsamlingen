@extends('layouts.application')

@section('content')
<h2>Logga in</h2>

<form method="post" action="/account/login">
    @csrf

    <label for="username">Användarnamn</label>
    <input type="text" name="username" id="username" class="text" size="50" value="{{ old('username') }}" />
    @error('username')
        <div class="error">{{ $message }}</div>
    @enderror

    <label for="password">Lösenord</label>
    <input type="password" name="password" id="password" class="text" size="50" />
    @error('password')
        <div class="error">{{ $message }}</div>
    @enderror

    <p><input type="checkbox" name="remember_me" value="true" /> Kom ihåg mig nästa gång</p>
    <br />
    <div class="buttons">
        <button type="submit" class="positive">
            Logga in
        </button>
    </div>
</form>

<hr />

<a href="/account/forgot">Glömt ditt lösenord?</a>

@endsection
