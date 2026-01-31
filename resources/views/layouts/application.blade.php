<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $page_title ?? 'Skivsamlingen' }}</title>

    <link href="/static/styles/grid.css" rel="stylesheet" media="screen" type="text/css" />
    <link href="/static/styles/standard.css" rel="stylesheet" media="screen" type="text/css" />
    <link href="/static/styles/tipTip.css" rel="stylesheet" media="screen" type="text/css" />
    <link rel="shortcut icon" href="/static/favicon.ico" />
    <link href="/static/styles/custom-theme/jquery-ui-1.8.2.custom.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>

    <script type="text/javascript">
    /* Placeholder polyfill - Licensed under Creative Commons Attribution 3.0 */
    $(document).ready(function() {
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
            if (input.value == '' || (loading && valueIsPlaceholder(input))) {
                if (isPassword(input)) {
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
                        input.focus();
                    } catch (e) { }
                }
                input.value = '';
                $(input).removeClass('placeholder');
            }
        }

        $(':text[placeholder],:password[placeholder]').each(function(index) {
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
            source: function(request, response) {
                $.ajax({
                    url: '/users/search',
                    type: 'post',
                    dataType: "json",
                    data: { query: request.term, _token: '{{ csrf_token() }}' },
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
                    window.location = "/users/" + $(ui.item).val();
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
    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', 'UA-1041788-5']);
      _gaq.push(['_trackPageview']);
      _gaq.push(['_trackPageLoadTime']);

      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();
    </script>
</head>
<body>

<div id="top">

<div id="header" class="container_12">

<h1 class="grid_12"><img src="/static/images/skivsamlingen.png" alt="Skivsamlingen"></h1>

<div class="clear"></div>

<div id="menu" class="grid_12">
    <a href="/" class="item">hem</a>
@auth
    <a href="/collection/record" class="item">ny skiva</a>
    <a href="/users/{{ Auth::user()->username }}" class="item">min profil</a>
    <a href="/account/logout" class="item">logga ut</a>
@else
    <a href="/account/login" class="item">logga in</a>
    <a href="/account/register" class="item">bli medlem</a>
@endauth

<form method="post" action="/users/search" name="search" id="search">
    @csrf
    <input type="text" name="query" id="query" placeholder="Sök medlemmar.." value="{{ old('query', '') }}" />
</form>
</div>

</div>

</div>

<div class="clear"></div>

<div id="page">
<div id="content" class="container_12">
    @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="notice error">{{ session('error') }}</div>
    @endif
    @yield('content')
<div class="clear"></div>
</div>

<div class="clear"></div>

<div id="footer" class="container_12">

<div class="grid_12 ">
<a href="/about">Om Skivsamlingen</a> | Denna webbsida använder <a href="/about">cookies</a>.
</div>
<div class="clear"></div>
</div>

</div>

</body>
</html>
