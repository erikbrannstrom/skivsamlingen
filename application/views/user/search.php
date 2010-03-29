<div id="" class="grid_12 "> <!-- Start: Main content -->
<h2><?=$query?></h2>
<?php foreach($users as $user): ?>
<a href="<?=site_url('user/profile/'.$user->username)?>"><?=$user->name?></a><br />
<?php endforeach; ?>
</div>