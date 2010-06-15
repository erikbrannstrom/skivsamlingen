<?php echo form_open('collection/comment'); ?>

<div class="grid_12"> <!-- Start: Content -->

<h2>Kommentera skiva</h2>

<p>Skriv en kommentar för <?=$record->name?> - <?=$record->title?>.</p>

<label>Kommentar</label>
<?=form_error('comment')?>
<input type="text" class="text" name="comment" maxlength="30" value="<?php echo set_value('comment', $record->comment);?>" />
<span>Max 255 tecken.</span>
<br />

<input type="hidden" name="record" value="<?=set_value('record', $record->id)?>" />

<div class="buttons">
    <button type="submit" class="positive" name="action" value="edit">
        <img src="<?=static_url('/images/icons/tick.png')?>" alt=""/>
        Spara
    </button>
    <button type="submit" class="positive" name="action" value="delete">
        <img src="<?=static_url('/images/icons/delete-icon.png')?>" alt=""/>
        Ta bort
    </button>
</div>

</div> <!-- End: Content -->