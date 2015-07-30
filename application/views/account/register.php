<script type="text/javascript" src="<?=static_url('scripts/jquery.ui.datepicker-sv.js')?>"></script>
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

<?php echo form_open('account/register'); ?>

<div class="grid_4">
<h2>Obligatoriskt</h2>

<label>Användarnamn</label>
<?=form_error('username')?>
<input type="text" class="text" name="username" maxlength="20" value="<?php echo set_value('username');?>" />
<span>Minst 3 och max 24 tecken. Får endast innehålla a-z, 0-9, streck, understreck och punkt.</span>

<label>Lösenord</label>
<?=form_error('password')?>
<input type="password" class="text" name="password" maxlength="20" />
<span>Minst 6 tecken.</span>

<label>Lösenord igen</label>
<?=form_error('passconf')?>
<input type="password" class="text" name="passconf" maxlength="20" />
<span>Måste vara exakt samma som lösenordet.</span>

<label>Robotfilter</label>
<?=form_error('captcha')?>
<span class="text">Vad är <?= $captcha_a ?> plus <?= $captcha_b ?>? Skriv svaret med siffror.</span>
<input type="hidden" name="captcha_a" value="<?= $captcha_a ?>" />
<input type="hidden" name="captcha_b" value="<?= $captcha_b ?>" />
<input type="text" class="text" name="captcha" maxlength="10" />

</div>

<div class="grid_4">
<h2>Rekommenderat</h2>
<label>E-post</label>
<?=form_error('email')?>
<input type="text" name="email" value="<?=set_value('email')?>" class="text" />
<span>En giltig <strong>e-postadress krävs för att kunna återställa lösenordet</strong> om du skulle glömma bort det. Adressen visas ej för andra medlemmar om du inte själv väljer att visa den.</span>

</div>

<div class="grid_4">
<h2>Valfritt</h2>
<label>Namn</label>
<?=form_error('name')?>
<input type="text" name="name" class="text" maxlength="50" value="<?=set_value('name')?>" />
<span>Max 50 tecken.</span>

<label>Kön</label>
<?=form_error('sex')?>
<input type="radio" name="sex" value="m" <?=set_radio('sex', 'm')?> /> Man<br />
<input type="radio" name="sex" value="f" <?=set_radio('sex', 'f')?> /> Kvinna<br />
<input type="radio" name="sex" value="x" <?=set_radio('sex', 'x', TRUE)?> /> Hemligt<br />

<label>Födelsedag</label>
<?=form_error('birth')?>
<input type="text" name="birth" value="<?=set_value('birth')?>" maxlength="10" class="text small" id="datepicker" />
<span>Fylls i som ÅÅÅÅ-MM-DD.</span>

</div>

<div class="clear"></div>

<div class="grid_4 push_8">

<div class="buttons">
    <button type="submit" class="positive">
        <img src="<?=static_url('/images/icons/tick.png')?>" alt=""/>
        Bli medlem
    </button>
</div>


</div>

</form>