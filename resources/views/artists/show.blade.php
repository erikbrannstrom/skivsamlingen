@extends('layouts.application')

@section('content')
<script type="text/javascript" src="/static/scripts/artists.js"></script>

<h2>{{ $artist->display_name }}</h2>

<div class="page-nav">

    {{ $records->links('vendor.pagination.simple') }}

    <x-sort-links
        :baseUrl="'/artists/' . $artist->id"
        :sorts="['Titel' => 'title', 'År' => 'year', 'Ägare' => 'owners']"
        :currentOrder="$order"
        :currentDirection="$direction"
    />

</div>

<table width="100%" cellspacing="0">
    <tr>
        <th align="left">Titel</th>
        <th align="left">År</th>
        <th align="left">Format</th>
        <th align="left">Ägare</th>
        @auth
        <th></th>
        @endauth
    </tr>
    @foreach($records as $record)
    <tr>
        <td>{{ $record->title }}</td>
        <td>{{ $record->year }}</td>
        <td>{{ $record->format }}</td>
        <td>{{ $record->users_count }}</td>
        @auth
        <td>
            @if($ownedRecordIds->contains($record->id))
                <img src="/static/images/icons/tick.png" width="14" title="I din samling" />
            @else
                <form method="POST" action="/collection/add" style="display:inline" class="add-to-collection">
                    @csrf
                    <input type="hidden" name="record_id" value="{{ $record->id }}" />
                    <input type="hidden" name="record_title" value="{{ $record->title }}" />
                    <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;" title="Lägg till i din samling"><img src="/static/images/icons/add.png" width="14" /></button>
                </form>
            @endif
        </td>
        @endauth
    </tr>
    @endforeach
</table>
@endsection

@section('sidebar')

@if($topCollectors->isNotEmpty())
<div class="box">
<h3>Toppsamlare</h3>
<ol>
@foreach($topCollectors as $collector)
<li><a href="/users/{{ $collector->username }}">{{ $collector->username }}</a> &ndash; {{ $collector->record_count }} {{ $collector->record_count == 1 ? 'skiva' : 'skivor' }}</li>
@endforeach
</ol>
</div>
@endif

@endsection
