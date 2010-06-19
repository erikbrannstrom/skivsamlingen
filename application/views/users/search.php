<div id="" class="grid_12 "> <!-- Start: Main content -->
<h2>Sökresultat</h2>
Din sökning efter <strong><?=$query?></strong> fick <?=count($users)?> <?=(count($users) == 1) ? 'träff' : 'träffar'?>. En sökning ger maximalt 20 resultat.
<?php if(count($users) > 0): ?>
<table class="search">
<tr>
    <th>&nbsp;</th>
    <th>Användarnamn</th>
    <th>Namn</th>
    <th>Antal skivor</th>
</tr>
<?php foreach($users as $user): ?>
<tr>
    <td><?=($user->sex == 'm') ? '<img src="'.static_url('images/icons/male.png').'" />' : (($user->sex == 'f') ? '<img src="'.static_url('images/icons/female.png').'" />' : '' )?></td>
	<td><a href="<?=site_url('users/'.$user->username)?>"><?=$user->username?></a></td>
	<td><?=$user->name?></td>
	<td><?=$user->num_records?> <?=($user->num_records == 1) ? 'skiva' : 'skivor'?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>