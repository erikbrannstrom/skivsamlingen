@extends('layouts.application')

@section('content')
<script type="text/javascript" src="/static/scripts/jquery.tipTip.minified.js"></script>
<script type="text/javascript">
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
</script>

<div class="grid_8"> <!-- Start: Main content -->
<h2>{{ $user->username }}</h2>

<x-collection-pagination
    :username="$user->username"
    :currentPage="$currentPage"
    :totalPages="$totalPages"
    :perPage="$perPage"
    :order="$order"
    :direction="$direction"
/>

<div style="float: right">
<x-sort-links
    :username="$user->username"
    :offset="$offset"
    :currentOrder="$order"
    :currentDirection="$direction"
/>
</div>
<div style="clear: both;"></div>

<table width="100%" cellspacing="0">
    @php
        $prev_artist = null;
        $even = false;
        $i = 0;
    @endphp
    @foreach($records as $record)
        @php $i++; @endphp
        @if($prev_artist === null || $prev_artist != $record->artist_id)
            @php $even = false; @endphp
    <tr class="artist">
        <td width="70%"><strong>{{ $record->artist->getDisplayNameAttribute() }}</strong></td>
        <td{{ Auth::id() == $user->id ? ' colspan="2"' : '' }} width="25%"><em>{{ $record->num_records }} {{ $record->num_records == 1 ? 'skiva' : 'skivor' }}</em></td>
    </tr>
        @endif
    <tr style="background-color: #fff">
        <td>{{ $record->title }}
        @if($record->pivot->comment)
            <img src="/static/images/icons/comment.png" title="{{ $record->pivot->comment }}" class="comment" />
        @endif
        </td>
        @if($record->year && $record->format)
            <td>{{ $record->year }} ({{ $record->format }})</td>
        @else
            <td>{{ $record->year }} {{ $record->format }}</td>
        @endif
        @if(Auth::id() == $user->id)
            <td width="20" valign="middle" align="center"><a href="/collection/record/{{ $record->pivot->id }}"><img src="/static/images/icons/edit.png" width="14" /></a></td>
        @endif
    </tr>
        @php
            $even = !$even;
            $prev_artist = $record->artist_id;
        @endphp
    @endforeach
</table>
</div> <!-- End: Main content -->

<div class="grid_4 sidebar"> <!-- Start: Sidebar -->

@if(Auth::id() == $user->id)
<div class="box">
<h3>Alternativ</h3>
<ul class="bullets">
    <li><a href="/users/{{ $user->username }}/export">Exportera skivsamling (XML)</a></li>
    <li><a href="/users/{{ $user->username }}/print">Visa utskriftsvy</a></li>
    <li><a href="/account/edit">Ändra dina uppgifter</a></li>
    <li><a href="/collection/record" class="item">Lägg till skiva</a></li>
</ul>
</div>
@endif

<div class="box">
<img alt="Profilbild från Gravatar.com" src="https://www.gravatar.com/avatar/{{ md5(strtolower(trim($user->email ?? ''))) }}?s=100&d=mm" class="gravatar" />
<h3>Profil</h3>
@if($is_supporter)
<em style="margin-top: -5px;display: block;">Supporter</em>
@endif
@if($user->name)
<strong>Namn:</strong> {{ $user->name }}<br />
@endif
@if($user->sex_display)
<strong>Kön:</strong> {{ $user->sex_display }}<br />
@endif
<strong>Antal skivor:</strong> {{ $num_records }} skivor<br />
@if($user->birth)
<strong>Ålder:</strong> {{ $user->birth->age }} år<br />
@endif
@if(Auth::check() && $user->public_email && $user->email)
<strong>E-post:</strong> <a href="mailto:{{ $user->email }}">{{ $user->email }}</a><br />
@endif
<strong>Medlem sedan:</strong> {{ $user->registered ? $user->registered->locale('sv')->isoFormat('D MMMM YYYY') : '' }}
<div class="clear"></div>
</div>

@if($user->about)
<div class="box">
<h3>Om mig</h3>
<p>
{!! nl2br(e($user->about)) !!}
</p>
</div>
@endif

<div class="box">
<h3>Populära artister</h3>
<ol>
@foreach($top_artists as $artist)
<li>{{ $artist->name }}, {{ $artist->records }} skivor</li>
@endforeach
</ol>
</div>

<div class="box">
<h3>Senaste skivorna</h3>
<ol>
@foreach($latest_records as $record)
<li>{{ $record->name }} - {{ $record->title }}</li>
@endforeach
</ol>
</div>

</div> <!-- End: Sidebar -->
@endsection
