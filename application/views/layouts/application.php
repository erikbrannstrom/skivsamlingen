<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title><?=$page_title?></title>

<link href="<?= base_url() ?>static/styles/grid.css" rel="stylesheet" media="screen" type="text/css" />
<link href="<?= base_url() ?>static/styles/standard.css" rel="stylesheet" media="screen" type="text/css" />
<link href="<?= base_url() ?>static/styles/tipTip.css" rel="stylesheet" media="screen" type="text/css" />
<link rel="shortcut icon" href="<?=static_url('favicon.ico')?>" />
<link href="<?= base_url() ?>static/styles/custom-theme/jquery-ui-1.8.2.custom.css" rel="stylesheet" type="text/css"/>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>

<script type="text/javascript">

/* This code is licensed under Creative Commons Attribution 3.0    *
 * You may share and remix the script so long as you attribute the *
 * original author, Andrew January.                                *
 * http://creativecommons.org/licenses/by/3.0/                     */

$(document).ready(function() {
    // Check to see if the browser already supports placeholder text (introduced in HTML5). If it does,
    // then we don't need to do anything.
    var i = document.createElement('input');
    if ('placeholder' in i) {
        return;
    }

	var isPassword = function(input) {
		return $(input).attr('realType') == 'password';
	}

	var valueIsPlaceholder = function(input) {
		return input.value == $(input).attr('placeholder');
	}

	var showPlaceholder = function(input, loading) {
		// FF and IE save values when you refresh the page. If the user refreshes the page
		// with the placeholders showing they will be the default values and the input fields won't
		// be empty. Using loading && valueIsPlaceholder is a hack to get around this and highlight
		// the placeholders properly on refresh.
		if (input.value == '' || (loading && valueIsPlaceholder(input))) {
			if (isPassword(input)) {
				// Must use setAttribute rather than jQuery as jQuery throws an exception
				// when changing type to maintain compatability with IE.
				// We use our own "compatability" method by simply swallowing the error.
				try {
					input.setAttribute('type', 'input');
				} catch (e) { }
			}
			input.value = $(input).attr('placeholder');
			$(input).addClass('placeholder');
		}
	}

	var hidePlaceholder = function(input) {
		if (valueIsPlaceholder(input) && $(input).hasClass('placeholder')) {
			if (isPassword(input)) {
				try {
					input.setAttribute('type', 'password');
					// Opera loses focus when you change the type, so we have to refocus it.
					input.focus();
				} catch (e) { }
			}

			input.value = '';
			$(input).removeClass('placeholder');
		}
	}

	$(':text[placeholder],:password[placeholder]').each(function(index) {
		// We change the type of password fields to text so their placeholder shows.
		// We need to store somewhere that they are actually password fields so we can convert
		// back when the users types something in.
		if ($(this).attr('type') == 'password') {
			$(this).attr('realType', 'password');
		}

		showPlaceholder(this, true);

		$(this).focus(function() { hidePlaceholder(this) });
		$(this).blur(function() { showPlaceholder(this, false) });
	});
});

$.widget("custom.searchcomplete", $.ui.autocomplete, {
    _renderMenu: function( ul, items ) {
        var self = this;
        $.each( items, function( index, item ) {
            self._renderItem( ul, item );
            if(item.type == 'total') {
                $('li:last a', ul).addClass('total');
            }
        });
    }
});

$(document).ready(function() {
    $("input#query").searchcomplete({
        source:
        function(request, response) {
            $.ajax({
                url: '<?=site_url('users/search')?>',
                type: 'post',
                dataType: "json",
                data: { query: request.term },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 3,
        delay: 450,
        select: function(event, ui) {
            if(ui.item.type == 'total') {
                $('#search').submit();
                return false;
            } else {
                window.location = "<?=site_url('users')?>/" + $(ui.item).val();
            }
        },
        focus: function(event, ui) {
            if(ui.item.type == 'total')
                return false;
            return true;
        }
    });
});
</script>

</head>
<body>
                
<div id="top">

<div id="header" class="container_12"> <!-- Start: header -->

<h1 class="grid_12"><img src="<?= base_url() ?>static/images/skivsamlingen.png" alt="Skivsamlingen"></h1>

<div class="clear"></div>

<div id="menu" class="grid_12">
    <a href="<?=site_url('')?>" class="item">hem</a>
<?php if($this->auth->isUser()): ?>
<a href="<?=site_url('collection/record')?>" class="item">ny skiva</a>
<a href="<?=site_url('users/'.$this->auth->getUsername())?>" class="item">min profil</a>
<a href="<?=site_url('account/logout')?>" class="item">logga ut</a>
<?php else: ?>
<a href="<?=site_url('account/login')?>" class="item">logga in</a>
<a href="<?=site_url('account/register')?>" class="item">bli medlem</a>
<?php endif; ?>

<form method="post" action="<?=site_url('users/search')?>" name="search" id="search">
	<input type="text" name="query" id="query" placeholder="Sök medlemmar.." value="<?=isset($_POST['query']) ? $_POST['query'] : ''?>" />
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
<a href="<?=site_url('about')?>">Om Skivsamlingen</a> | Denna webbsida använder <a href="<?=site_url('about')?>">cookies</a>.
<span class="streambur"><a href="http://streambur.se/"><img src="<?=static_url('images/streambur-logo.png')?>" /></a></span>
</div>
<div class="clear"></div>
</div> <!-- End: footer -->

</div> <!-- End: page -->

</body>
</html>