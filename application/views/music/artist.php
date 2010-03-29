<div id="" class="grid_12 "> <!-- Start: Main content -->
<h2><?=$artist->name?></h2>
<?=$artist->name?> har <?=$artist->num_records?> skivor inlagda på Skivsamlingen.<br />
<?php foreach($artist->Records as $record): ?>
<?=$record->title?><br />
<?php endforeach; ?>
</div>