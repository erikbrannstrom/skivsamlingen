<?php echo form_open('collection/delete'); ?>

<div class="grid_12"> <!-- Start: Content -->

<h2>Ta bort skiva</h2>

<p>Är du säker på att du vill ta bort <?=$record->name?> - <?=$record->title?>?</p>

<input type="hidden" name="record" value="<?=set_value('record', $record->id)?>" />

<p>
<div class="buttons">
    <button type="submit" class="positive">
        <img src="<?=static_url('/images/icons/delete-icon.png')?>" alt=""/>
        Ta bort
    </button>
</div>
<a href="<?=site_url('users/'.$this->auth->getUsername())?>">Gå tillbaka</a>
</p>

</div> <!-- End: Content -->