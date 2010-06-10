<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title><?=$page_title?></title>

<style type="text/css">
    body { font-family: Verdana, sans-serif; font-size: 12px; }
</style>

</head>
<body>

<table width="800" cellspacing="0">
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
		<td style="padding: 0.3em"><?=$record->year?> (<?=$record->format?>)</td>
		</td>
	</tr>
	<?php
	$even = !$even;
	$prev_artist = $record->artist_id;
	endforeach; ?>
</table>

</body>
</html>