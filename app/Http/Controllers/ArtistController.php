<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\RecordUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ArtistController extends Controller
{
    public function show(int $id): View
    {
        $artist = Artist::findOrFail($id);

        $records = $artist->records()
            ->withCount('users')
            ->orderBy('year')
            ->orderBy('title')
            ->get();

        $ownedRecordIds = Auth::check()
            ? Auth::user()->records()->pluck('records.id')->toArray()
            : [];

        return view('artists.show', [
            'artist' => $artist,
            'records' => $records,
            'ownedRecordIds' => $ownedRecordIds,
            'page_title' => $artist->display_name,
        ]);
    }
}
