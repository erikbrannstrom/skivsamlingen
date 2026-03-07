@extends('layouts.application')

@section('content')
<script type="text/javascript" src="/static/scripts/artists.js"></script>
<div class="grid_12">

<h2>{{ $artist->display_name }}</h2>

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
            @if(in_array($record->id, $ownedRecordIds))
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

</div>
@endsection

