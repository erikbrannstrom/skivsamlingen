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
  <p><strong>Nej!</strong> Det enda du behöver är en webbläsare - för bästa resultat rekommenderas senaste versionen av <a href="http://www.mozilla.com/firefox/">Mozilla Firefox</a> eller <a href="http://www.google.com/chrome/">Google Chrome</a> - och en internetuppkoppling (vilket du ju verkar ha). Annars är det bara att sätta igång med samlandet och organiserandet.</p>

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

<ul class="donate">
  <li>
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
    <input type="hidden" name="cmd" value="_s-xclick">
    <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
    ">
    <input type="image" src="https://www.paypalobjects.com/sv_SE/SE/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal – ett tryggt och smidigt sätt att betala på nätet med.">
    <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
    </form>
  </li>

  <li>
    <script id='fbbvyns'>(function(i){var f,s=document.getElementById(i);f=document.createElement('iframe');f.src='//api.flattr.com/button/view/?uid=erik.brannstrom&url='+encodeURIComponent(document.URL);f.title='Flattr';f.height=62;f.width=55;f.style.borderWidth=0;s.parentNode.insertBefore(f,s);})('fbbvyns');</script>
  </li>
</ul>

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
<?php foreach($latest_records as $record): ?>
    <a href="<?=site_url('users/'.$record->username)?>"><?=$record->name?> - <?=$record->title?></a><br />
<?php endforeach; ?>
</div>

<div style="padding-top: 15px; background-color: #fff">
<script type="text/javascript">
var uri = 'https://impse.tradedoubler.com/imp?type(js)pool(402779)a(1301841)epi(<?=($this->auth->isUser()) ? $this->auth->getUserID() : '0'?>)' + new String (Math.random()).substring (2, 11);
document.write('<sc'+'ript type="text/javascript" src="'+uri+'" charset="ISO-8859-1"></sc'+'ript>');
</script>
</div>

</div> <!-- End: Sidebar -->