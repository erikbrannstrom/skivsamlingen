<div id="" class="grid_12 "> <!-- Start: Main content -->
<h2><?=$query?></h2>
<?php if(count($users) == 0): ?>
Inga anv�ndare kunde hittas.
<?php else: ?>
<table>
<?php foreach($users as $user): ?>
<tr>
	<td><a href="<?=site_url('user/profile/'.$user->username)?>"><?=$user->username?></a></td>
	<td><?=$user->name?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>