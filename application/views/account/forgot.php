<div class="grid_12 "> <!-- Start: Main content -->

<?php foreach($this->notice->getAllKeys() as $key): ?>
<?=$this->notice->get($key)?>
<?php endforeach; ?>

<h2>Glömt lösenord</h2>

<p>Om du har angett en e-postadress för ditt konto så är det bara att fylla i den eller ditt
    användarnamn nedan så kommer en länk för återställning av lösenordet skickas till dig.
    Mailet bör komma inom ett par minuter. Kontrollera också din spambox om mailet inte tycks anlända.</p>
<p>Länken är endast giltig i 48 timmar.</p>

<?=form_open('account/forgot')?>
<p>
<label>Användarnamn / E-post</label> <input type="text" name="username" class="text" />
</p>
<br />
<div class="buttons">
    <button type="submit" class="positive">
        <img src="<?=static_url('/images/icons/tick.png')?>" alt=""/>
        Skicka
    </button>
</div>
</form>

</div>