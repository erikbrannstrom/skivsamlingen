@extends('layouts.application')

@section('content')
<script type="text/javascript" src="/static/scripts/jquery.ui.datepicker-sv.js"></script>
<script type="text/javascript">
$(function() {
    $.datepicker.setDefaults($.datepicker.regional['sv']);
    $('#datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        yearRange: '-100:-10'
    });
});
</script>

<div style="width: 47%; float: left; padding: 0 1em;">
<h2>Inställningar</h2>

<form method="post" action="/account/edit">
    @csrf

@error('name')
    <div class="error">{{ $message }}</div>
@enderror
<label>Namn</label> <input type="text" name="name" class="text" maxlength="50" value="{{ old('name', $user->name) }}" />
<span>Max 50 tecken.</span>

@error('sex')
    <div class="error">{{ $message }}</div>
@enderror
<label>Kön</label>
<input type="radio" name="sex" value="m" {{ old('sex', $user->sex) === 'm' ? 'checked' : '' }} /> Man<br />
<input type="radio" name="sex" value="f" {{ old('sex', $user->sex) === 'f' ? 'checked' : '' }} /> Kvinna<br />
<input type="radio" name="sex" value="x" {{ old('sex', $user->sex) === 'x' ? 'checked' : '' }} /> Hemligt<br />
<span>Välj hemligt om du inte vill att någon ska veta.</span>

@error('email')
    <div class="error">{{ $message }}</div>
@enderror
<label>E-post</label> <input type="text" name="email" value="{{ old('email', $user->email) }}" class="text" />
<span>E-postadressen kan användas om du glömmer ditt lösenord.</span>

@error('public_email')
    <div class="error">{{ $message }}</div>
@enderror
<label>Publik e-post</label>
<input type="radio" name="public_email" value="1" {{ old('public_email', $user->public_email) == '1' ? 'checked' : '' }} /> Ja<br />
<input type="radio" name="public_email" value="0" {{ old('public_email', $user->public_email) == '0' ? 'checked' : '' }} /> Nej<br />
<span>Om du vill att andra medlemmar ska kunna se din e-postadress, välj ja.</span>

@error('birth')
    <div class="error">{{ $message }}</div>
@enderror
<label>Födelsedag</label> <input type="text" name="birth" value="{{ old('birth', $user->birth ? $user->birth->format('Y-m-d') : '') }}" maxlength="10" class="text small" id="datepicker" />
<span>Fylls i som ÅÅÅÅ-MM-DD.</span>

@error('about')
    <div class="error">{{ $message }}</div>
@enderror
<label>Om mig</label> <textarea cols="40" rows="6" name="about">{{ old('about', $user->about) }}</textarea>
<span>Max 3000 tecken.</span>

@error('per_page')
    <div class="error">{{ $message }}</div>
@enderror
<label>Skivor per sida</label> <input type="text" name="per_page" maxlength="6" class="text x-small" value="{{ old('per_page', $user->per_page) }}" />
<span>Max 100 skivor per sida.</span>

<br /><input type="submit" value="Klar" />
</form>
</div>

<div style="width: 47%; float: right; padding: 0 1em;">

<h2>Ändra lösenord</h2>

<form method="post" action="/account/password">
    @csrf

@error('current_password')
    <div class="error">{{ $message }}</div>
@enderror
<label>Nuvarande lösenord</label> <input type="password" name="current_password" maxlength="50" class="text" />
<hr class="ruler" />
@error('new_password')
    <div class="error">{{ $message }}</div>
@enderror
<label>Nytt lösenord</label> <input type="password" name="new_password" maxlength="50" class="text" />
@error('new_password_confirmation')
    <div class="error">{{ $message }}</div>
@enderror
<label>Upprepa lösenord</label> <input type="password" name="new_password_confirmation" maxlength="50" class="text" />

<br /><input type="submit" value="Ändra" />

</form>

<h2>Ta bort ditt konto</h2>
Om du av någon anledning inte längre vill att ditt konto på Skivsamlingen ska finnas kvar, fyll i ditt lösenord samt kontrollfältet och klicka därefter Ta bort. Observera att alla skivor du lagt in kommer att försvinna och <strong>informationen ej går att återskapa</strong>.

<form method="post" action="/account/unregister">
    @csrf

@error('password')
    <div class="error">{{ $message }}</div>
@enderror
<label>Lösenord</label> <input type="password" name="password" maxlength="50" class="text" />
@error('confirmation')
    <div class="error">{{ $message }}</div>
@enderror
<label>Kontrollfält</label> <input type="text" name="confirmation" maxlength="50" class="text" />
<span>Skriv "ta bort" i fältet ovan, utan citationstecken.</span>

<br /><input type="submit" value="Ta bort" />
</form>

</div>
@endsection
