<div id="" class="grid_8 "> <!-- Start: Main content -->
<?php foreach($this->notice->getAllKeys() as $key): ?>
<?=$this->notice->get($key)?>
<?php endforeach; ?>
<h2><?=$user->username?></h2>

<?=$pagination?>

<table width="100%" cellspacing="0">
	<?php
	$prev_artist = NULL;
	$even = false;
	$i = 0;
	foreach ($records as $record):
		if($prev_artist == NULL || $prev_artist != $record->artist_id): 
			$even = false;?>
	<tr>
		<td width="70%" style="border-bottom: 1px #eaeaea solid; font-size: 1.1em; padding: 0.1em 0"><strong><?php
		$has_the = stripos($record->name, 'the ');
		if($has_the !== FALSE && $has_the == 0) {
			echo rtrim(substr($record->name, 4), ",") . ", " . substr($record->name, 0, 3);
		} else {
			echo $record->name;
		}
		?></strong></td>
		<td width="25%" style="border-bottom: 1px #eaeaea solid; font-size: 1.1em; padding: 0.1em 0"><em><?=$record->num_records?> <?=($record->num_records == 1) ? 'skiva' : 'skivor'?></em></td>
	</tr>
	<?php
		$i++;
		endif; ?>
	<tr style="background-color: <?=$even ? '#fff' : '#fff'?>">
		<td style="padding: 0.3em"><?=$record->title?></td>
		<td style="padding: 0.3em"><?=$record->year?> (<?=$record->format?>)
		<?php if($user->id == $this->auth->getUserID()): ?>
		<a href="<?=site_url('collection/delete/'.$record->id)?>">DEL</a>
		<?php endif; ?>
		</td>
	</tr>
	<?php
	$even = !$even;
	$prev_artist = $record->artist_id;
	endforeach; ?>
</table>
</div> <!-- End: Main content -->

<div class="grid_4 sidebar"> <!-- Start: Sidebar -->

<div class="box">
<h3>Profil</h3>
<?php if($user->name): ?>
<strong>Namn:</strong> <?=$user->name?><br />
<?php endif; ?>
<?php if($user->getSex()): ?>
<strong>Kön:</strong> <?=$user->getSex()?><br />
<?php endif; ?>
<strong>Antal skivor:</strong> <?=$num_records?><br />
<?php if($user->getAge()): ?>
<strong>Ålder:</strong> <?=$user->getAge()?> år<br />
<?php endif; ?>
<strong>Medlem sedan:</strong> <?=$user->getRegistered()?>
</div>

<div class="box">
<h3>Om mig</h3>
<p>
<?=nl2br($user->about)?>
</p>
</div>

<div class="box">
<h3>Populära artister</h3>
<ol>
<?php
$all = $user->getTopArtists(5);
foreach($all as $artist): ?>
<li><?= $artist->name?>, <?= $artist->records ?> skivor</li>
<?php endforeach; ?>
</ol>
</div>

<div class="box">
<h3>Senaste skivorna</h3>
<ol>
<?php
$all = $user->getLatestRecords(5);
foreach($all as $record): ?>
<li><?= $record->name?> - <?= $record->title ?></li>
<?php endforeach; ?>
</ol>
</div>

</div> <!-- End: Sidebar -->
