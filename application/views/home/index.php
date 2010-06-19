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
  
<?php else: $user = $this->auth->getUser();?>
<h2>Hej <?=($user->name) ? $user->name : $user->username?>!</h2>
<p>Du har nu loggat in på nya Skivsamlingen. Oavsett om du har varit medlem sedan starten eller precis registrerade dig så följer här en introduktion till hur man använder sidan.</p>

<p><strong>Lägga till skivor</strong> - I menyn längst upp finns en direktlänk för att lägga till nya skivor. Du kan även komma åt denna sida via din egen profil.</p>

<p><strong>Ändra inställningar</strong> - Genom att gå in på din profil via menyn längt upp på sidan så får du tillgång till en rad alternativ i högerspalten.</p>

<p>Förhoppningsvis räcker det för att du ska komma igång! Om det fortfarande finns frågetecken så går det bra att skicka ett mail så ska vi försöka räta ut dem.</p>

<p><strong>Med vänliga hälsningar,</strong><br />Erik

<?php endif; ?>

<h2>Statistik</h2>

<div style="width: 45%; float: left;">
<h3>Medlemmar</h3>
<table class="statistics" cellspacing="0">
<tr>
	<th>Totalt</th>
    <td><?=$members['total']?></td>
</tr>
<tr>
	<th>Nya denna vecka</th>
    <td><?=$members['this_week']?></td>
</tr>
<tr class="separator">
	<th>Nya förra veckan</th>
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
</tr><!--
<tr>
	<th>Antal skivor</th>
    <td><?=$total_recs?></td>
</tr>
<tr>
	<th>Skivor / användare</th>
    <td><?=round($total_recs / $members['total'], 1)?></td>
</tr>-->
</table>
</div>

<div style="width: 45%; float: left;">
<h3>Största samlingarna</h3>
<ol style="list-style-position: inside;">
<?php foreach($toplist as $user): ?>
<li><a href="<?=site_url('/users/'.$user->username)?>"><?=$user->username?></a> med <?=$user->recs?></li>
<?php endforeach; ?>
</ol>
</div>

<div style="width: 45%; float: left;">
<h3>Populäraste artisterna</h3>
<ol style="list-style-position: inside;">
<? foreach($popular_artists as $row): ?>
<li><strong><?=$row->name?></strong> med <?=$row->records?> skivor</li>
<? endforeach; ?>
</ol>
</div>

<div style="width: 45%; float: left;">
<h3>Populäraste albumen</h3>
<ol style="list-style-position: inside;">
<? foreach($popular_albums as $row): ?>
<li><strong><?=$row->name?> - <?=$row->title?></strong> med <?=$row->records?> skivor</li>
<? endforeach; ?>
</ol>
</div>

</div> <!-- End: Main content -->
<div id="" class="grid_4 sidebar"> <!-- Start: Sidebar -->

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
<? foreach($latest_records as $record): ?>
    <a href="<?=site_url('users/'.$record->username)?>"><?=$record->name?> - <?=$record->title?></a><br />
<? endforeach; ?>
</div>

</div> <!-- End: Sidebar -->