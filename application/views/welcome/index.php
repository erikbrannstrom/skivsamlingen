<div class="grid_8 "> <!-- Start: Main content -->
<?php foreach($this->notice->getAllKeys() as $key): ?>
<?=$this->notice->get($key)?>
<?php endforeach; ?>

<?php if($this->auth->isGuest()): ?>

<h2>Vad är Skivsamlingen?</h2>
  <div style="float:right; padding: 10px; margin: 5px; border: 1px solid #cccccc">
<strong>Köp dina skivor här</strong><br />

<a href="http://clk.tradedoubler.com/click?p=46&a=1301841&g=16153296" target="_blank">
CDON</a> <img src="http://impse.tradedoubler.com/imp?type(inv)g(16153296)a(1301841)">
<br />
<a href="http://clk.tradedoubler.com/click?p=19124&a=1301841&g=402088" target="_blank">

Ginza</a> <img src="http://impse.tradedoubler.com/imp?type(inv)g(402088)a(1301841)">
<br />
<a href="http://clk.tradedoubler.com/click?p=37616&a=1301841&g=16159818" target="_blank">
CD WOW!</a> <img src="http://impse.tradedoubler.com/imp?type(inv)g(16159818)a(1301841)">
<br />
<a href="http://clk.tradedoubler.com/click?p=50697&a=1301841&g=16726524" target="_blank">
Megastore</a> <img src="http://impse.tradedoubler.com/imp?type(inv)g(16726524)a(1301841)">

</div>
  <p>Skivsamlingen är en plats där du som skivsamlare kan föra register över vilka skivor du har i samlingen. Kanske behövs en omorganisation bland skivorna? Kanske är det så att du snabbt och enkelt vill kunna visa för andra vad du har för musiksmak? Eller vill du bara ha järnkoll på din egen samling? Registrera dig nu och börja lägga in dina skivor!</p>

  <h2>Hur använder man Skivsamlingen?</h2>
  <p>Till att börja med <a href="http://skivsamlingen.se/account">registrerar</a> du dig. Det enda du behöver fylla i är ett <strong>användarnamn</strong> och ett <strong>lösenord</strong> och sen är det bara att sätta igång!</p>

  <p>När du är registrerad och inloggad har du en meny uppe till höger. Där kan du klicka dig in på din egen profil, men just nu kommer du inte åt din skivsamling eftersom det inte finns några skivor. Klicka på <strong>Ny skiva</strong> i menyn under ditt användarnamn och snart har du lagt till din allra första skiva!</p>

  
  <h2>Kostar det något?</h2>
  <p><strong>Nej!</strong> Det enda du behöver är en webbläsare - för bästa resultat rekommenderas <a href="http://www.mozilla-europe.org/en/firefox/">Firefox 3+</a> eller Google Chrome - och en internetuppkoppling (vilket du ju verkar ha). Annars är det bara att sätta igång med samlandet och organiserandet.
  
<?php endif; ?>

<h2>Statistik</h2>

<div style="width: 200px; float: left;">
<h3>Medlemmar</h3>
<table cellspacing="0">
<tr>
	<th>Totalt</th>
    <td><?=$members['total']?></td>
</tr>
<tr>
	<th>Denna veckan</th>
    <td><?=$members['this_week']?></td>
</tr>
<tr class="separator">
	<th>Förra veckan</th>
    <td><?=$members['last_week']?></td>
</tr>
<tr>
	<th>Män</th>
    <td><?=$sex['male']?></td>
</tr>
<tr>
	<th>Kvinnor</th>
    <td><?=$sex['female']?></td>
</tr>
<tr class="separator">
	<th>Okänt</th>
    <td><?=$sex['unknown']?></td>
</tr>
<tr>
	<th>Antal skivor</th>
    <td><?=$total_recs?></td>
</tr>
<tr>
	<th>Skivor / användare</th>
    <td><?=round($total_recs / $members['total'], 1)?></td>
</tr>
</table>
</div>

<div style="width: 200px; float: left;">
<h3>Största samlingarna</h3>
<ol style="list-style-position: inside;">
<?php foreach($toplist as $user): ?>
<li><a href="<?=site_url('/users/'.$user->username)?>"><?=$user->username?></a> (<?=$user->recs?>)</li>
<?php endforeach; ?>
</ol>
</div>

<div style="width: 200px; float: left;">
<h3>Populäraste artisterna</h3>
<ol style="list-style-position: inside;">
<? foreach($popular_artists as $row): ?>
<li><strong><?=$row->name?></strong> med <?=$row->records?> skivor</li>
<? endforeach; ?>
</ol>
</div>

<div style="clear: both; height: 0px; display: block"></div>

<div style="width: 400px; float: left;">
<h3>Populäraste albumen</h3>
<ol style="list-style-position: inside;">
<? foreach($popular_albums as $row): ?>
<li><strong><?=$row->name?> - <?=$row->title?></strong> med <?=$row->records?> skivor</li>
<? endforeach; ?>
</ol>
</div>

</div> <!-- End: Main content -->
<div id="" class="grid_4 "> <!-- Start: Sidebar -->

<div class="box">

  <h3>Nyheter</h3>
  <p>
	<?php foreach ($news->result() as $item): ?>
	<h4><?= $item->title ?></h4>
	<?= $item->body ?>
	<?php endforeach; ?>
  </p>

</div>

<div class="box">

<h3>Senaste skivor</h3>

<a href="http://skivsamlingen.se/users/marmaskt">Desmond Dekker And The Specials - King of Kings</a><br />

<a href="http://skivsamlingen.se/users/marmaskt">Lee Perry - Skanking whit the upsetter Rare dubs 1971-1974</a><br />

<a href="http://skivsamlingen.se/users/marmaskt">Lee Perry - The Upsetter</a><br />

<a href="http://skivsamlingen.se/users/marmaskt">Linval Thompson Meets King Tubbys - In a reggae dub style/Dis a yard dub</a><br />

<a href="http://skivsamlingen.se/users/marmaskt">Louis Armstrong - And his all stars. Rocking Chair</a><br />

</div>

</div> <!-- End: Sidebar -->