<div id="" class="grid_12 "> <!-- Start: Main content -->
<?php foreach($this->notice->getAllKeys() as $key): ?>
<?=$this->notice->get($key)?>
<?php endforeach; ?>

<h2>Logga in</h2>

<?php echo form_open('account/login'); ?>

<label for="username">Användarnamn</label>
<input type="text" name="username" class="text" size="50" />

<label for="password">Lösenord</label>
<input type="password" name="password" class="text" size="50" />

<p><input type="checkbox" name="remember_me" value="true" /> Kom ihåg inloggningen i 30 dagar</p>
<br />
<div class="buttons">
    <button type="submit" class="positive">
        <img src="<?=static_url('/images/icons/tick.png')?>" alt=""/>
        Logga in
    </button>
</div>

</form>

</div>