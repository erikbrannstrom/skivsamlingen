<div class="grid_8 "> <!-- Start: Main content -->
    <h2>Kan vi göra någonting bättre?</h2>
    <p>Det är tråkigt att inte ha dig som medlem längre. Vi vore väldigt tacksamma om du kunde ta en minut och berätta varför du valt att säga upp ditt medlemskap på Skivsamlingen.</p>
    <p>Vi strävar hela tiden efter att göra sidan bättre för alla användare så alla förslag och tankar uppskattas!</p>
    <p><strong>Med vänliga hälsningar,</strong><br />Erik Brännström</p>
    <p>
<?=form_open('home/unregistered')?>
    <label for="name">Namn</label> <input type="text" name="name" class="text" value="<?=(!empty($name)) ? $name . " ($username)" : $username ?>" /><br />
    <label for="email">E-post</label> <input type="text" name="email" class="text" value="<?=$email?>" /><br />
    <textarea name="message"></textarea><br />
    <input type="submit" value="Skicka">
</form>
    </p>

</div> <!-- End: Main content -->
<div class="grid_4 "> <!-- Start: Sidebar -->

<div class="box">

  <h3>Nyheter</h3>
  <p>
	<?php foreach ($news->result() as $item): ?>
	<h4><?= $item->title ?></h4>
	<?= $item->body ?>
	<?php endforeach; ?>
  </p>

</div>

</div> <!-- End: Sidebar -->