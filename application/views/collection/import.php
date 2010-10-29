<div class="grid_12"> <!-- Start: Content -->

<?php foreach($this->notice->getAllKeys() as $key): ?>
<?=$this->notice->get($key)?>
<?php endforeach; ?>

<h2>Importera skivsamling</h2>

<p class="error">
	<strong>Viktigt!</strong> Om du väljer att importera en fil till Skivsamlingen så kommer den
	att ta bort alla skivor du just nu har i din samling och ersätta dessa.
	Det är rekommenderat att du först exporterar din samling så att den går att återställa
	om det skulle behövas.
</p>

<?= form_open_multipart('collection/import')?>
<label for="userfile">XML-fil:</label> <input type="file" name="userfile" size="30" />

<p>
<div class="buttons">
    <button type="submit" class="positive">
        Importera
    </button>
</div>
</p>

</div> <!-- End: Content -->