<script type="text/javascript" src="<?=static_url('scripts/jquery.tipTip.minified.js')?>"></script>
<script type="text/javascript">
<!--
var orig = '#f0f0f0';
$(function() {
    $("tr:not(.artist)").hover(function() {
        orig = $(this).css('background-color');
        $(this).css('background-color', '#f0f0f0');
    }, function() {
        $(this).css('background-color', orig);
    });
    $(".comment").tipTip({
        defaultPosition: 'right',
        edgeOffset: 8,
        delay: 350,
        fadeIn: 150,
        fadeOut: 150
    });
});
//-->
</script>

<div id="" class="grid_8 "> <!-- Start: Main content -->
<?php foreach($this->notice->getAllKeys() as $key): ?>
<?=$this->notice->get($key)?>
<?php endforeach; ?>
<h2><?=$user->username?></h2>

<?=$pagination?>

<div style="float: right">
<ul class="pagination order">
<? foreach($sort_links as $link): ?>
	<?= $link ?>
<? endforeach; ?>
</ul>
</div>
<div style="clear: both;"></div>



<table width="100%" cellspacing="0">
	<?php
	$prev_artist = NULL;
	$even = false;
	$i = 0;
	foreach ($records as $record):
		if($prev_artist == NULL || $prev_artist != $record->artist_id): 
			$even = false;?>
	<tr class="artist">
		<td width="70%"><strong><?php
		$has_the = stripos($record->name, 'the ');
		if($has_the !== FALSE && $has_the == 0) {
			$name = rtrim(substr($record->name, 4), ",") . ", " . substr($record->name, 0, 3);
		} else {
			$name = $record->name;
		}
                echo htmlspecialchars($name, ENT_COMPAT, 'UTF-8');
		?></strong></td>
		<td<?=($user->id == $this->auth->getUserID()) ? ' colspan="2"' : ''?> width="25%"><em><?=$record->num_records?> <?=($record->num_records == 1) ? 'skiva' : 'skivor'?></em></td>
	</tr>
	<?php
		$i++;
		endif; ?>
	<tr style="background-color: <?=$even ? '#fff' : '#fff'?>">
            <td><?=htmlspecialchars($record->title, ENT_COMPAT, 'UTF-8')?>
        <?php if($record->comment): ?>
            <img src="<?=static_url('images/icons/comment.png')?>" title="<?=$record->comment?>" class="comment" />
        <?php endif; ?>
        </td>
        <?php if($record->year && $record->format): ?>
            <td><?=$record->year?> (<?=$record->format?>)</td>
        <?php else: ?>
            <td><?=$record->year?> <?=$record->format?></td>
        <?php endif; ?>
        <?php if($user->id == $this->auth->getUserID()): ?>
            <td width="20" valign="middle" align="center"><a href="<?=site_url('collection/record/'.$record->id)?>"><img src="<?=static_url('images/icons/edit.png')?>" width="14" /></a></td>
        <?php endif; ?>
	</tr>
	<?php
	$even = !$even;
	$prev_artist = $record->artist_id;
	endforeach; ?>
</table>
</div> <!-- End: Main content -->

<div class="grid_4 sidebar"> <!-- Start: Sidebar -->

<? if($user->id == $this->auth->getUserId()): ?>
<div class="box">
<h3>Alternativ</h3>
<ul class="bullets">
    <li><a href="<?=site_url('users/'.$user->username.'/export')?>">Exportera skivsamling (XML)</a></li>
    <li><a href="<?=site_url('users/'.$user->username.'/print')?>">Visa utskriftsvy</a></li>
    <li><a href="<?=site_url('account/edit')?>">Ändra dina uppgifter</a></li>
    <li><a href="<?=site_url('collection/record')?>" class="item">Lägg till skiva</a></li>
</ul>
</div>
<? endif; ?>

<div class="box">
<h3>Profil</h3>
<?php if($user->name): ?>
<strong>Namn:</strong> <?=$user->name?><br />
<?php endif; ?>
<?php if($user->sex): ?>
<strong>Kön:</strong> <?=$user->sex?><br />
<?php endif; ?>
<strong>Antal skivor:</strong> <?=$num_records?> skivor<br />
<?php if($user->birth): ?>
<strong>Ålder:</strong> <?=years_since($user->birth)?> år<br />
<?php endif; ?>
<?php if($this->auth->isUser() && $user->public_email && $user->email):?>
<strong>E-post:</strong> <a href="mailto:<?=$user->email?>"><?=$user->email?></a><br />
<?php endif;
    setlocale(LC_TIME, 'sv_SE');
?>
<strong>Medlem sedan:</strong> <?=strtolower(strftime('%e %B %Y', mysql_to_unix($user->registered)))?>
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
foreach($top_artists as $artist): ?>
<li><?= $artist->name?>, <?= $artist->records ?> skivor</li>
<?php endforeach; ?>
</ol>
</div>

<div class="box">
<h3>Senaste skivorna</h3>
<ol>
<?php
foreach($latest_records as $record): ?>
<li><?= $record->name?> - <?= $record->title ?></li>
<?php endforeach; ?>
</ol>
</div>

</div> <!-- End: Sidebar -->
