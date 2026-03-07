<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\RecordUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ArtistController extends Controller
{
    public function show(Request $request, int $id): View
    {
        $artist = Artist::findOrFail($id);

        $columnMap = ['title' => 'title', 'year' => 'year', 'owners' => 'users_count'];
        $requestedOrder = $request->input('order');
        $order = array_key_exists($requestedOrder, $columnMap) ? $requestedOrder : 'year';
        $direction = strtolower($request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = $artist->records()
            ->withCount('users')
            ->orderBy($columnMap[$order], $direction);

        if ($order !== 'title') {
            $query->orderBy('title');
        }

        $records = $query->paginate(25)->withQueryString();

        $ownedRecordIds = Auth::check()
            ? Auth::user()->records()->where('records.artist_id', $artist->id)->pluck('records.id')
            : collect();

        return view('artists.show', [
            'artist' => $artist,
            'records' => $records,
            'ownedRecordIds' => $ownedRecordIds,
            'order' => $order,
            'direction' => $direction,
            'page_title' => $artist->display_name,
        ]);
    }
}
