<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page_title ?? 'Skivsamlingen' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Abril+Fatface&family=Jost:wght@400;600;700&family=Lora:wght@400;700&display=swap" rel="stylesheet">
    <link href="/static/styles/app.css" rel="stylesheet">
    <link href="/static/styles/tipTip.css" rel="stylesheet" media="screen">
    <link rel="shortcut icon" href="/static/favicon.ico">
    <link href="/static/styles/custom-theme/jquery-ui-1.8.2.custom.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>

    <script>
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
    <script>
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

<div id="header">

<h1>
    <svg class="logo-record" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="40" height="40" aria-hidden="true">
        <circle cx="22" cy="22" r="21" fill="#1A1A2E"/>
        <circle cx="22" cy="22" r="19" fill="none" stroke="#2A2A3E" stroke-width="0.9"/>
        <circle cx="22" cy="22" r="16" fill="none" stroke="#252535" stroke-width="0.7"/>
        <circle cx="22" cy="22" r="13" fill="none" stroke="#2A2A3E" stroke-width="0.6"/>
        <circle cx="22" cy="22" r="10" fill="none" stroke="#252535" stroke-width="0.5"/>
        <circle cx="22" cy="22" r="8" fill="#C5391A"/>
        <circle cx="22" cy="22" r="6" fill="none" stroke="#E86040" stroke-width="0.5"/>
        <circle cx="22" cy="22" r="2.5" fill="#1A1A2E"/>
    </svg>
    <span class="logo-text">
        <span class="logo-name">Skivsamlingen</span>
        <span class="logo-tagline">musik är en livsstil.</span>
    </span>
</h1>

    <nav>
        <div id="menu">
            <a href="/" class="item">hem</a>
        @auth
            <a href="/collection/record" class="item">ny skiva</a>
            <a href="/users/{{ Auth::user()->username }}" class="item">min profil</a>
            <a href="/account/logout" class="item">logga ut</a>
        @else
            <a href="/account/login" class="item">logga in</a>
            <a href="/account/register" class="item">bli medlem</a>
        @endauth
        </div>

        <form method="post" action="/users/search" name="search" id="search">
            @csrf
            <input type="text" name="query" id="query" placeholder="Sök medlemmar.." value="{{ old('query', '') }}" />
        </form>
    </nav>

</div>

</div>

<div id="page">
<div id="content">
    @if(session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif
    <div class="layout">
        <div class="main">@yield('content')</div>
        @hasSection('sidebar')
        <div class="sidebar">@yield('sidebar')</div>
        @endif
    </div>
</div>

<div id="footer">
<a href="/about">Om Skivsamlingen</a> | Denna webbsida använder <a href="/about">cookies</a>.
</div>

</div>

</body>
</html>
