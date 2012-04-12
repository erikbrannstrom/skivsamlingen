<div class="grid_6"> <!-- Start: Content -->
<?=$this->notice->get()?>
<h2><?=($id == 0) ? 'Ny' : 'Redigera'?> skiva</h2>
<?= form_open('collection/record') ?>
<?= form_hidden('id', set_value('id', $record->id))?>
<?=form_error('nonce')?>
<label>Artist/grupp</label>
<?=form_error('artist')?>
<input type="text" class="text" name="artist" maxlength="100" value="<?php echo set_value('artist', $record->name);?>" />
<span>Artisten eller gruppen som släppt skivan, t.ex. The Beatles, U2 eller Radiohead.
För samlingsskivor med blandade artister, använd gärna Various eller V/A. Max 64 tecken.</span>

<label>Titel</label>
<?=form_error('title')?>
<input type="text" class="text" name="title" maxlength="150" value="<?php echo set_value('title', $record->title);?>" />
<span>Skivans titel, t.ex. Abbey Road, The Joshua Tree eller Kid A. Max 150 tecken.</span>

<label>År</label>
<?=form_error('year')?>
<input type="text" class="text" name="year" maxlength="4" value="<?php echo set_value('year', $record->year);?>" />
<span>Året för originalrelease med fyra siffror, t.ex. 1999 eller 2010. Kan lämnas tomt.</span>

<label>Format</label>
<?=form_error('format')?>
<input type="text" class="text" name="format" maxlength="30" value="<?php echo set_value('format', $record->format);?>" />
<span>Formatet på skivan, t.ex. CD, 12" eller CD/DVD. Max 30 tecken. Kan lämnas tomt.</span>

<?php if($id == 0): ?>
<label>Kommentar</label>
<?=form_error('comment')?>
<input type="text" class="text" name="comment" maxlength="255" value="<?php echo set_value('comment');?>" />
<span>Max 255 tecken.</span>
<?php endif; ?>

<br />
<div class="buttons">
    <button type="submit" class="positive">
        <img src="<?=static_url('/images/icons/tick.png')?>" alt=""/> 
        <?=($id == 0) ? 'Lägg till' : 'Spara'?>
    </button>
<?=form_close()?>
<?php if($id > 0): ?>
<?= form_open('collection/delete', '', array('nonce' => false)); ?>
<input type="hidden" name="record" value="<?=set_value('record', $record->id)?>" />
<input type="hidden" name="action" value="delete" />

    <button type="submit" class="positive">
        <img src="<?=static_url('/images/icons/delete-icon.png')?>" alt=""/>
        Ta bort skiva
    </button>
<?=form_close()?>
<?php endif; ?>
</div>

</div>
<div class="grid_6">
<?php if($id > 0): ?>
<h2>Kommentera skiva</h2>

<p>Skriv en kommentar för <?=$record->name?> - <?=$record->title?>.</p>

<?= form_open('collection/comment', '', array('nonce' => false)); ?>
<label>Kommentar</label>
<?=form_error('comment')?>
<input type="text" class="text" name="comment" maxlength="255" value="<?php echo set_value('comment', $record->comment);?>" />
<span>Max 255 tecken.</span>
<br />

<input type="hidden" name="record" value="<?=set_value('record', $record->id)?>" />
<input type="hidden" name="action" value="edit" />

<div class="buttons">
    <button type="submit" class="positive">
        <img src="<?=static_url('/images/icons/tick.png')?>" alt=""/>
        Spara
    </button>
<?=form_close()?>
<?=form_open('collection/comment', '', array('nonce' => false)); ?>
<input type="hidden" name="record" value="<?=set_value('record', $record->id)?>" />
<input type="hidden" name="action" value="delete" />

    <button type="submit" class="positive">
        <img src="<?=static_url('/images/icons/delete-icon.png')?>" alt=""/>
        Ta bort kommentar
    </button>
</div>
<?=form_close()?>
<?php endif; ?>

</div>