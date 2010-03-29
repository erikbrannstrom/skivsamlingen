<div id="" class="grid_8 "> <!-- Start: Main content -->
<h2><?=$user->username?></h2>
<table width="100%" cellspacing="0">
	<?php
	$prev_artist = NULL;
	$even = false;
	foreach ($user->Records as $record):
		if($prev_artist == NULL || $prev_artist != $record->Artist->name): 
			$even = false;?>
	<tr>
		<td width="70%" style="border-bottom: 1px #eaeaea solid; font-size: 1.1em; padding: 0.1em 0"><strong><?=$record->Artist->name?></strong></td>
		<td width="25%" style="border-bottom: 1px #eaeaea solid; font-size: 1.1em; padding: 0.1em 0"><em><?=$num_records[$record->Artist->id]['num']?> <?=($num_records[$record->Artist->id]['num'] == 1) ? 'skiva' : 'skivor'?></em></td>
	</tr>
	<?php
		endif; ?>
	<tr style="background-color: <?=$even ? '#fff' : '#fff'?>">
		<td style="padding: 0.3em"><?=$record->title?></td>
		<td style="padding: 0.3em"><?=$record->year?> (<?=$record->format?>)</td>
	</tr>
	<?php
	$even = !$even;
	$prev_artist = $record->Artist->name;
	endforeach; ?>
</table>
</div> <!-- End: Main content -->

<div class="grid_4 sidebar"> <!-- Start: Sidebar -->

<div class="box">
<h3>Profil</h3>
<strong>Namn:</strong> <?=$user->name?><br />
<?php if($sex = $user->getGender()): ?>
<strong>Kön:</strong> <?=$sex?><br />
<?php endif; ?>
<strong>Ålder:</strong> <?=$user->getAge()?> år<br />
<strong>Medlem sedan:</strong> <?=$user->getRegDate()?>
</div>

<div class="box">
<h3>Om mig</h3>
<p>
<?=nl2br($user->about)?>
</p>
</div>

<div class="box">
<h3>Senaste skivorna</h3>
<ul>
<?php
$all = $user->getLatestRecords(5);
foreach($all as $record): ?>
<li><?= $record->Artist->name?> - <?= $record->title ?><br /></li>
<?php endforeach; ?>
</ul>
</div>

<div class="box">
<h3>Populära artister</h3>
<ol>
<?php
$all = $user->getTopArtists(5);
foreach($all as $artist): ?>
<li><?= $artist->name?>, <?= $artist->num ?> skivor</li>
<?php endforeach; ?>
</ol>
</div>

</div> <!-- End: Sidebar -->
