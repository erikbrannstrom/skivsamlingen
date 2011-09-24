<div class="grid_12"> <!-- Start: Content -->

<?php foreach($this->notice->getAllKeys() as $key): ?>
<?=$this->notice->get($key)?>
<?php endforeach; ?>

<h2>Importera skivsamling</h2>

<p class="error">
	<strong>Viktigt!</strong> Om du väljer att importera en fil till Skivsamlingen så kommer den
	att ta bort alla skivor du just nu har i din samling och ersätta dessa.
	Det är rekommenderat att du först exporterar din samling så att den går att återställa
	om det skulle behövas. <strong>Funktionen är begränsad till en import i timmen.</strong>
</p>

<p>Godkända format för import är samlingar exporterade dels från Skivsamlingen och dels från <a href="http://www.pop.nu/">pop.nu</a>. Om du ska importera en samling från pop.nu är det viktigt att välja XML-format samt kryssa i fälten Artist, Titel, Format och Utgivningsår.</p>

<?php if($user->last_import !== null): ?>
<p>Din senaste import gjordes den <?=strtolower(strftime('%e %B %Y kl. %H:%M', $user->last_import))?>.
<?php if($user->last_import > time()-60*60): ?>
Du måste vänta till <?=strtolower(strftime('kl. %H:%M', $user->last_import+60*60))?>.
<?php endif; ?>
</p>
<?php endif; ?>
<?php if($this->auth->getUser()->last_import === null || $this->auth->getUser()->last_import <= (time() - 60*60)): ?>

<?= form_open_multipart('collection/import')?>
<label for="userfile">XML-fil:</label> <input type="file" name="userfile" size="30" />

<p>
<div class="buttons">
    <button type="submit" class="positive">
        Importera
    </button>
</div>
</p>

<?php endif; ?>

</div> <!-- End: Content -->