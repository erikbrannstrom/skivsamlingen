<div class="grid_8 "> <!-- Start: Main content -->

<h2>Nyheter</h2>

<?=$pagination?>
<div style="clear: both"></div>

<? foreach($news as $item): ?>
<h2><?=$item->title?></h2>
<div style="font-size: 0.8em"><?=$item->posted?></div>
<?=$item->body?>
<?endforeach; ?>

</div> <!-- End: Main content -->

<div class="grid_4 sidebar"> <!-- Start: Sidebar -->

    <div class="box">
        <h3>Prenumerera via RSS</h3>
        <p>Vill du enkelt hålla koll på när det sker uppdateringar på Skivsamlingen?</p>
        <p>Allt du behöver är en RSS-läsare såsom <a href="http://www.google.com/reader/" target="_blank">Google Reader</a> där du kan
            lägga till vår RSS-feed. Klicka på ikonen nedan för adressen!</p>
        <p><a href="<?=site_url('news/rss')?>"><img src="<?=static_url('images/icons/feed-icon-28x28.png')?>" /></a></p>
    </div>

</div> <!-- End: Sidebar -->
