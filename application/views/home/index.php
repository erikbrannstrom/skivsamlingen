<div class="grid_8 "> <!-- Start: Main content -->
<?php foreach($this->notice->getAllKeys() as $key): ?>
<?=$this->notice->get($key)?>
<?php endforeach; ?>

<?php if($this->auth->isGuest()): ?>

<h2>Vad är Skivsamlingen?</h2>
  <p>Skivsamlingen är en plats där du som skivsamlare kan föra register över vilka skivor du har i samlingen. Kanske behövs en omorganisation bland skivorna? Kanske är det så att du snabbt och enkelt vill kunna visa för andra vad du har för musiksmak? Eller vill du bara ha järnkoll på din egen samling? Registrera dig nu och börja lägga in dina skivor!</p>

  <h2>Hur använder man Skivsamlingen?</h2>
  <p>Till att börja med <a href="<?=site_url('account/register')?>">registrerar</a> du dig. Det enda du behöver fylla i är ett <strong>användarnamn</strong> och ett <strong>lösenord</strong> och sen är det bara att sätta igång!</p>
  <p>När du sedan är inloggad så kommer du åt dina nyvunna alternativ under din profil. Denna sida tillsammans med sidan där du kan lägga till nya skivor kommer du åt i menyn under loggan.</p>
  
  <h2>Kostar det något?</h2>
  <p><strong>Nej!</strong> Det enda du behöver är en webbläsare - för bästa resultat rekommenderas <a href="http://www.mozilla.com/firefox/">Firefox 3.6</a> eller <a href="http://www.google.com/chrome/">Google Chrome</a> - och en internetuppkoppling (vilket du ju verkar ha). Annars är det bara att sätta igång med samlandet och organiserandet.
  
<?php else: $user = $this->auth->getUser();?>
<h2>Hej <?=($user->name) ? $user->name : $user->username?>!</h2>
<p>Du har nu loggat in på nya Skivsamlingen. Oavsett om du har varit medlem sedan starten eller precis registrerade dig så följer här en introduktion till hur man använder sidan.</p>

<p><strong>Lägga till skivor</strong> - I menyn längst upp finns en direktlänk för att lägga till nya skivor. Du kan även komma åt denna sida via din egen profil.</p>

<p><strong>Ändra inställningar</strong> - Genom att gå in på din profil via menyn längt upp på sidan så får du tillgång till en rad alternativ i högerspalten.</p>

<p>Förhoppningsvis räcker det för att du ska komma igång! Om det fortfarande finns frågetecken så går det bra att skicka ett mail så ska vi försöka räta ut dem.</p>

<p><strong>Med vänliga hälsningar,</strong><br />Erik

<?php endif; ?>

<h2>Statistik</h2>

<div style="width: 48%; float: left;">
<h3>Medlemmar</h3>
<table style="width: 100%" class="statistics" cellspacing="0">
<tr>
	<th>Antal medlemmar</th>
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
    <td><?=$sex['male_percent']?>% (<?=$sex['male']?> st)</td>
</tr>
<tr>
	<th>Kvinnor</th>
    <td><?=$sex['female_percent']?>% (<?=$sex['female']?> st)</td>
</tr>
<tr class="separator">
	<th>Okänt</th>
    <td><?=$sex['unknown_percent']?>% (<?=$sex['unknown']?> st)</td>
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

<div style="width: 48%; float: right;">
<h3>Största samlingarna</h3>
<table style="width: 100%" class="statistics" cellspacing="0">
<?php
$i = 1;
foreach($toplist as $user): ?>
    <tr>
        <td><?=$i++?>.</td>
        <td><a href="<?=site_url('/users/'.$user->username)?>"><?=$user->username?></a></td>
        <td><?=$user->recs?> skivor</td>
    </tr>
<?php endforeach; ?>
</table>
</div>
<div style="clear:both"></div>
<div style="width: 48%; float: left;">
<h3>Populäraste artisterna</h3>
<table style="width: 100%" class="statistics" cellspacing="0">
<?php
$i = 1;
foreach($popular_artists as $row): ?>
    <tr>
        <td><?=$i++?>.</td>
        <td><?=$row->name?></td>
        <td><?=$row->records?> skivor</td>
    </tr>
<?php endforeach; ?>
</table>
</div>

<div style="width: 48%; float: right;">
<h3>Populäraste albumen</h3>
<table style="width: 100%" class="statistics" cellspacing="0">
<?php
$i = 1;
foreach($popular_albums as $row): ?>
    <tr>
        <td><?=$i++?>.</td>
        <td><strong><?=$row->name?></strong><br />
        <?=$row->title?></td>
        <td><?=$row->records?> skivor</td>
    </tr>
<?php endforeach; ?>
</table>
</div>

</div> <!-- End: Main content -->
<div id="" class="grid_4 sidebar"> <!-- Start: Sidebar -->

<div class="box">

  <h3>Senaste nytt</h3>
  <p>
	<?php foreach ($news->result() as $item): ?>
	<h4><?= $item->title ?></h4>
	<?= $item->body ?>
	<?php endforeach; ?>
  </p>
  <p><?=anchor('news', 'Alla nyheter')?></p>

</div>

<div class="box">

<h3>Senaste skivor</h3>
<? foreach($latest_records as $record): ?>
    <a href="<?=site_url('users/'.$record->username)?>"><?=$record->name?> - <?=$record->title?></a><br />
<? endforeach; ?>
</div>

<div style="padding-top: 15px; background-color: #fff">
<script type="text/javascript">
var uri = 'http://impse.tradedoubler.com/imp?type(js)pool(402779)a(1301841)epi(<?=($this->auth->isUser()) ? $this->auth->getUserID() : '0'?>)' + new String (Math.random()).substring (2, 11);
document.write('<sc'+'ript type="text/javascript" src="'+uri+'" charset="ISO-8859-1"></sc'+'ript>');
</script>
</div>

</div> <!-- End: Sidebar -->