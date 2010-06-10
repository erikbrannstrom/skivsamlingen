<?php foreach($this->notice->getAllKeys() as $key): ?>
<?=$this->notice->get($key)?>
<?php endforeach; ?>
<div style="width: 47%; float: left; padding: 0 1em;">
<h2>Inställningar</h2>

<?php if($this->form_validation->error_string): ?>
<div class="error">
<?=$this->form_validation->error_string?>
</div>
<? endif; ?>

<?=form_open('account/edit')?>
<?=form_error('name')?>
<label>Namn</label> <input type="text" name="name" class="text" maxlength="50" value="<?=set_value('name', $user->name)?>" />
<span>Max 50 tecken.</span>

<?=form_error('sex')?>
<label>Kön</label>
<input type="radio" name="sex" value="m" <?=set_radio('sex', 'm', ($user->sex == 'm'))?> /> Man<br />
<input type="radio" name="sex" value="f" <?=set_radio('sex', 'f', ($user->sex == 'f'))?> /> Kvinna<br />
<input type="radio" name="sex" value="x" <?=set_radio('sex', 'x', ($user->sex == 'x'))?> /> Hemligt<br />
<span>Välj hemligt om du inte vill att någon ska veta.</span>

<?=form_error('email')?>
<label>E-post</label> <input type="text" name="email" value="<?=set_value('email', $user->email)?>" class="text" />
<span>E-postadressen kan användas om du glömmer ditt lösenord.</span>

<?=form_error('public_email')?>
<label>Publik e-post</label>
<input type="radio" name="public_email" value="1" <?=set_radio('public_email', '1', ($user->public_email == '1'))?> /> Ja<br />
<input type="radio" name="public_email" value="0" <?=set_radio('public_email', '0', ($user->public_email == '0'))?> /> Nej<br />
<span>Om du vill att andra medlemmar ska kunna se din e-postadress, välj ja.</span>

<?=form_error('birth')?>
<label>Födelsedag</label> <input type="text" name="birth" value="<?=is_set($user->birth, '')?>" maxlength="10" class="text small" />
<span>Fylls i som ÅÅÅÅ-MM-DD.</span>

<?=form_error('about')?>
<label>Om mig</label> <textarea cols="40" rows="6" name="about"><?=set_value('about', $user->about)?></textarea>
<span>Max 3000 tecken.</span>

<?=form_error('per_page')?>
<label>Skivor per sida</label> <input type="text" name="per_page" maxlength="6" class="text x-small" value="<?=set_value('per_page', $user->per_page)?>" />
<span>Max 100 skivor per sida.</span>

<br /><input type="submit" value="Klar" />
</form>
</div>

<div style="width: 47%; float: right; padding: 0 1em;">

<h2>Ändra lösenord</h2>

<?=form_open('account/password')?>
<?=form_error('ch_oldpass')?>
<label>Nuvarande lösenord</label> <input type="password" name="ch_oldpass" maxlength="50" class="text" />
<hr class="ruler" />
<?=form_error('ch_newpass')?>
<label>Nytt lösenord</label> <input type="password" name="ch_newpass" maxlength="50" class="text" />
<?=form_error('ch_newpassconf')?>
<label>Upprepa lösenord</label> <input type="password" name="ch_newpassconf" maxlength="50" class="text" />

<br /><input type="submit" value="Ändra" />

</form>

<h2>Ta bort ditt konto</h2>
Om du av någon anledning inte längre vill att ditt konto på Skivsamlingen ska finnas kvar, fyll i ditt lösenord samt kontrollfältet och klicka därefter Ta bort. Observera att alla skivor du lagt in kommer att försvinna och <strong>informationen ej går att återskapa</strong>.

<?=form_open('account/unregister')?>
<label>Lösenord</label> <input type="password" name="rem_password" maxlength="50" class="text" />
<label>Kontrollfält</label> <input type="text" name="rem_confirm" maxlength="50" class="text" />
<span>Skriv "ta bort" i fältet ovan, utan citationstecken.</span>

<br /><input type="submit" value="Ta bort" />

</div>