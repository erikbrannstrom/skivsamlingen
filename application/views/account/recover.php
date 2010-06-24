<div class="grid_12 "> <!-- Start: Main content -->

<?=form_open('account/recover/'.$this->uri->segment(3).'/'.$this->uri->segment(4))?>

<h2>Välj nytt lösenord</h2>
<p>
    Fyll i ditt nya lösenord nedan. Därefter kommer du skickas vidare till inloggningen.
</p>

<p>
<?=form_error('password')?>
<label>Nytt lösenord</label> <input type="password" name="password" maxlength="50" class="text" />
<?=form_error('passconf')?>
<label>Upprepa lösenord</label> <input type="password" name="passconf" maxlength="50" class="text" />
</p>
<br />
<input type="submit" value="Ändra" />
</form>

</div>