<div id="" class="grid_12 "> <!-- Start: Main content -->
<?php foreach($this->notice->getAllKeys() as $key): ?>
<?=$this->notice->get($key)?>
<?php endforeach; ?>

<h2>Logga in</h2>

<?php echo form_open('account/login'); ?>


<h5>Username</h5>
<input type="text" name="username" value="" size="50" />



<h5>Password</h5>
<input type="password" name="password" value="" size="50" />

<p><input type="checkbox" name="remember_me" value="true" /> Kom ihåg inloggningen i 30 dagar</p>

<div><input type="submit" value="Submit" /></div>

</form>

</div>