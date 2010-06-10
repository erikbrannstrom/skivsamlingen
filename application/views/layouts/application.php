<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title><?=$page_title?></title>

<link href="<?= base_url() ?>static/styles/grid.css" rel="stylesheet" media="screen" type="text/css" />
<link href="<?= base_url() ?>static/styles/standard.css" rel="stylesheet" media="screen" type="text/css" />
</head>
<body>
                
<div id="top">

<div id="header" class="container_12"> <!-- Start: header -->

<h1 class="grid_12"><img src="<?= base_url() ?>static/images/skivsamlingen.png" alt="Skivsamlingen"></h1>

<div class="clear"></div>

<div id="menu" class="grid_12">
<a href="<?=site_url('welcome')?>" class="item">hem</a>
<?php if($this->auth->isUser()): ?>
<a href="<?=site_url('collection/add')?>" class="item">ny skiva</a>
<a href="<?=site_url('users/'.$this->auth->getUsername())?>" class="item">min profil</a>
<a href="<?=site_url('account/logout')?>" class="item">logga ut</a>
<?php else: ?>
<a href="<?=site_url('account/login')?>" class="item">logga in</a>
<a href="<?=site_url('account/register')?>" class="item">bli medlem</a>
<?php endif; ?>

<form method="post" action="<?=site_url('user/search')?>" name="search" id="search">
	<input type="text" name="query" id="query" value="" />
</form>
</div>

</div> <!-- End: header -->

</div>

<div class="clear"></div>

<div id="page"> <!-- Start: page -->
<div id="content" class="container_12"> <!-- Start: content -->
    <?=notifications($this->auth->getUserId())?>
	<?=$yield?>
<div class="clear"></div>
</div> <!-- End: content -->

<div class="clear"></div>


<div id="footer" class="container_12"> <!-- Start: footer -->

<div class="grid_12 ">
Denna webbsida använder cookies.
<span class="streambur">Streambur Consulting</span>
</div>

</div> <!-- End: footer -->

</div> <!-- End: page -->

</body>
</html>