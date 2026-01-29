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

<form method="post" action="/account/register">
    @csrf

<div class="grid_4">
<h2>Obligatoriskt</h2>

<label>Användarnamn</label>
@error('username')
    <div class="error">{{ $message }}</div>
@enderror
<input type="text" class="text" name="username" maxlength="24" value="{{ old('username') }}" />
<span>Minst 3 och max 24 tecken. Får endast innehålla a-z, 0-9, streck, understreck och punkt.</span>

<label>Lösenord</label>
@error('password')
    <div class="error">{{ $message }}</div>
@enderror
<input type="password" class="text" name="password" maxlength="50" />
<span>Minst 6 tecken.</span>

<label>Lösenord igen</label>
@error('password_confirmation')
    <div class="error">{{ $message }}</div>
@enderror
<input type="password" class="text" name="password_confirmation" maxlength="50" />
<span>Måste vara exakt samma som lösenordet.</span>

<label>Robotfilter</label>
@error('captcha')
    <div class="error">{{ $message }}</div>
@enderror
<span class="text">Vad är {{ $captcha_a }} plus {{ $captcha_b }}? Skriv svaret med siffror.</span>
<input type="hidden" name="captcha_a" value="{{ $captcha_a }}" />
<input type="hidden" name="captcha_b" value="{{ $captcha_b }}" />
<input type="text" class="text" name="captcha" maxlength="10" value="{{ old('captcha') }}" />

</div>

<div class="grid_4">
<h2>Rekommenderat</h2>
<label>E-post</label>
@error('email')
    <div class="error">{{ $message }}</div>
@enderror
<input type="text" name="email" value="{{ old('email') }}" class="text" />
<span>En giltig <strong>e-postadress krävs för att kunna återställa lösenordet</strong> om du skulle glömma bort det. Adressen visas ej för andra medlemmar om du inte själv väljer att visa den.</span>

</div>

<div class="grid_4">
<h2>Valfritt</h2>
<label>Namn</label>
@error('name')
    <div class="error">{{ $message }}</div>
@enderror
<input type="text" name="name" class="text" maxlength="50" value="{{ old('name') }}" />
<span>Max 50 tecken.</span>

<label>Kön</label>
@error('sex')
    <div class="error">{{ $message }}</div>
@enderror
<input type="radio" name="sex" value="m" {{ old('sex') === 'm' ? 'checked' : '' }} /> Man<br />
<input type="radio" name="sex" value="f" {{ old('sex') === 'f' ? 'checked' : '' }} /> Kvinna<br />
<input type="radio" name="sex" value="x" {{ old('sex', 'x') === 'x' ? 'checked' : '' }} /> Hemligt<br />

<label>Födelsedag</label>
@error('birth')
    <div class="error">{{ $message }}</div>
@enderror
<input type="text" name="birth" value="{{ old('birth') }}" maxlength="10" class="text small" id="datepicker" />
<span>Fylls i som ÅÅÅÅ-MM-DD.</span>

</div>

<div class="clear"></div>

<div class="grid_4 push_8">

<div class="buttons">
    <button type="submit" class="positive">
        <img src="/static/images/icons/tick.png" alt=""/>
        Bli medlem
    </button>
</div>

</div>

</form>
@endsection
