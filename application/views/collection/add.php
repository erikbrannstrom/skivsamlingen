<?php echo form_open('collection/add'); ?>

<div class="grid_12"> <!-- Start: Content -->

<h2>Ny skiva</h2>

<label>Artist/grupp</label>
<?=form_error('artist')?>
<input type="text" class="text" name="artist" maxlength="100" value="<?php echo set_value('artist');?>" />
<span>Artisten eller gruppen som släppt skivan, t.ex. The Beatles, U2 eller Radiohead.
För samlingsskivor med blandade artister, använd gärna Various eller V/A. Max 100 tecken.</span>

<label>Titel</label>
<?=form_error('title')?>
<input type="text" class="text" name="title" maxlength="150" value="<?php echo set_value('title');?>" />
<span>Skivans titel, t.ex. Abbey Road, The Joshua Tree eller Kid A. Max 150 tecken.</span>

<label>År</label>
<?=form_error('year')?>
<input type="text" class="text" name="year" maxlength="4" value="<?php echo set_value('year');?>" />
<span>Året för originalrelease med fyra siffror, t.ex. 1999 eller 2010. Kan lämnas tomt.</span>

<label>Format</label>
<?=form_error('format')?>
<input type="text" class="text" name="format" maxlength="30" value="<?php echo set_value('format');?>" />
<span>Formatet på skivan, t.ex. CD, 12" eller CD/DVD. Kan lämnas tomt.</span>
<br />
<div class="buttons">
    <button type="submit" class="positive">
        <img src="<?=static_url('/images/icons/tick.png')?>" alt=""/> 
        Lägg till
    </button>
</div>

</div> <!-- End: Content -->