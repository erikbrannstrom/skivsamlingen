<div class="grid_8 "> <!-- Start: Main content -->
<?php foreach($this->notice->getAllKeys() as $key): ?>
<?=$this->notice->get($key)?>
<?php endforeach; ?>

<h2>Om Skivsamlingen</h2>

  <h3>Varför startades Skivsamlingen?</h3>
  <p>För några år sedan fanns en sida vid namn Skivsamling.nu där tusentals användare samlade och visade upp sina skivsamlingar. Utan förvarning försvann dock sidan från nätet. Ett par år passerade utan att den kom tillbaka, så tillslut beslöt jag att det var dags att starta upp en ny sådan sida.</p>
  <p>Notera dock att Skivsamlingen inte har någonting att göra med numera försvunna skivsamling.nu - bortsett från själv idén samt att den adressen nu går vidare till denna sida.</p>

  <h3>Vem driver Skivsamlingen?</h3>
  <p>Skivsamlingen startades och administreras av Erik Brännström. Eftersom Skivsamlingen är en tjänst med användarskapat innehåll - d.v.s. alla medlemmar kan själva lägga till skivor i databasen - så är det egentligen användarna själva som ser till att hålla sidan igång.</p>

<h2>Om cookies</h2>
<p>Enligt <a href="http://www.riksdagen.se/webbnav/index.aspx?nid=3911&bet=2003:389" target="_blank">lagen om elektronisk kommunikation</a> skall alla webbplatser som använder cookies meddela besökarna om följande:</p>
<h3>Webbplatsen innehåller cookies</h3>
<p>Skivsamlingen innehåller cookies av typerna <em>cookies</em> och <em>session cookies</em>.</p>

<h3>Vad cookies är och vad de används till</h3>
<p>Session cookies sparas bara så länge webbläsaren är igång i datorns arbetsminne. Dessa används på Skivsamlingen för att hålla reda på en användarens inloggning så att denne inte behöver logga in på varje sida.</p>
<p>Cookies sparas i en textfil på besökarens dator. På Skivsamlingen skapas en sådan när en användare loggar in och väljer att bli ihågkommen. Den tas bort automatiskt vid utloggning.</p>
<h3>Hur besökaren kan undvika cookies</h3>
<p>Skivsamlingen skapar endast cookies hos användare som loggar in och använder sidan. Eventuellt kan även tredjepartsannonsörer skapa cookies. Cookies kan enkelt rensas i de flesta webbläsare om så önskas.</p>

</div> <!-- End: Main content -->
<div class="grid_4 sidebar"> <!-- Start: Sidebar -->

<div class="box">

    <h3>Kontakt</h3>
  <p>
      <strong>Erik Brännström</strong><br />
      <em>Administratör</em><br />
      <a href="mailto:erik.brannstrom@skivsamlingen.se">erik.brannstrom@skivsamlingen.se</a>
  </p>

</div>

<div style="padding-top: 15px; background-color: #fff">
<script type="text/javascript">
var uri = 'https://impse.tradedoubler.com/imp?type(js)pool(402779)a(1301841)epi(<?=($this->auth->isUser()) ? $this->auth->getUserID() : '0'?>)' + new String (Math.random()).substring (2, 11);
document.write('<sc'+'ript type="text/javascript" src="'+uri+'" charset="ISO-8859-1"></sc'+'ript>');
</script>
</div>

</div> <!-- End: Sidebar -->