@extends('layouts.application')

@section('content')

<h2>Nyheter</h2>

{{ $news->links('vendor.pagination.simple') }}

@forelse($news as $item)
<h2>{{ $item->title }}</h2>
<div style="font-size: 0.8em">{{ $item->posted->format('Y-m-d H:i:s') }}</div>
{!! $item->body !!}
@empty
<p>Inga nyheter att visa.</p>
@endforelse

@endsection

@section('sidebar')

    <div class="box">
        <h3>Prenumerera via RSS</h3>
        <p>Vill du enkelt hålla koll på när det sker uppdateringar på Skivsamlingen?</p>
        <p>Allt du behöver är en RSS-läsare där du kan
            lägga till vår RSS-feed. Klicka på ikonen nedan för adressen!</p>
        <p><a href="/news/rss" title="RSS-feed" class="rss-link"><i class="fa-solid fa-rss"></i></a></p>
    </div>

@endsection
