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
<input type="hidden" name="action" value="edit" />

<div class="buttons">
    <button type="submit" class="positive">
        <img src="<?=static_url('/images/icons/tick.png')?>" alt=""/>
        Spara
    </button>
</form>
<?php echo form_open('collection/comment'); ?>
<input type="hidden" name="record" value="<?=set_value('record', $record->id)?>" />
<input type="hidden" name="action" value="delete" />

    <button type="submit" class="positive">
        <img src="<?=static_url('/images/icons/delete-icon.png')?>" alt=""/>
        Ta bort
    </button>
</div>
</form>

</div> <!-- End: Content -->