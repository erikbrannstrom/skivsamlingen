<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecordRequest;
use App\Models\Artist;
use App\Models\Record;
use App\Models\RecordUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CollectionController extends Controller
{
    /**
     * Show the new record form.
     */
    public function new(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/account/login')
                ->with('error', 'Du måste vara inloggad för att kunna göra detta.');
        }

        $record = (object) [
            'id' => 0,
            'name' => '',
            'title' => '',
            'year' => '',
            'format' => '',
            'comment' => '',
        ];

        return view('collection.record', [
            'record' => $record,
            'id' => 0,
        ]);
    }

    /**
     * Show the edit record form.
     */
    public function edit(int $id): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/account/login')
                ->with('error', 'Du måste vara inloggad för att kunna göra detta.');
        }

        $entry = RecordUser::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $rec = Record::with('artist')->findOrFail($entry->record_id);

        $record = (object) [
            'id' => $entry->id,
            'name' => $rec->artist->name ?? '',
            'title' => $rec->title,
            'year' => $rec->year,
            'format' => $rec->format,
            'comment' => $entry->comment,
        ];

        return view('collection.record', [
            'record' => $record,
            'id' => $id,
        ]);
    }

    /**
     * Handle new record form submission.
     */
    public function create(StoreRecordRequest $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/account/login');
        }

        $record = $this->findOrCreateRecord($request);

        RecordUser::create([
            'user_id' => Auth::id(),
            'record_id' => $record->id,
            'comment' => $request->input('comment'),
        ]);

        $message = $request->input('artist') . ' - ' . $request->input('title') . ' har lagts till.';

        return redirect('/collection/record')->with('success', $message);
    }

    /**
     * Handle edit record form submission.
     */
    public function update(StoreRecordRequest $request, int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/account/login');
        }

        $entry = RecordUser::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        $comment = $entry?->comment;
        $entry?->delete();

        $record = $this->findOrCreateRecord($request);

        RecordUser::create([
            'user_id' => Auth::id(),
            'record_id' => $record->id,
            'comment' => $comment,
        ]);

        $message = $request->input('artist') . ' - ' . $request->input('title') . ' har uppdaterats.';

        return redirect('/users/' . Auth::user()->username)->with('success', $message);
    }

    /**
     * Find an existing record by exact match or create a new one.
     */
    private function findOrCreateRecord(StoreRecordRequest $request): Record
    {
        $artist = Artist::firstOrCreate(['name' => $request->input('artist')]);

        return Record::whereRaw('title COLLATE utf8_bin = ?', [$request->input('title')])
            ->where('artist_id', $artist->id)
            ->where('year', $request->input('year'))
            ->where('format', $request->input('format'))
            ->first() ?? Record::create([
                'artist_id' => $artist->id,
                'title' => $request->input('title'),
                'year' => $request->input('year'),
                'format' => $request->input('format'),
            ]);
    }

    /**
     * Show delete confirmation page.
     */
    public function delete(int $id): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/account/login');
        }

        $entry = RecordUser::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $rec = Record::with('artist')->findOrFail($entry->record_id);

        $record = (object) [
            'id' => $entry->id,
            'name' => $rec->artist->name ?? '',
            'title' => $rec->title,
        ];

        return view('collection.delete', ['record' => $record]);
    }

    /**
     * Handle record deletion.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/account/login');
        }

        $id = $request->input('record');

        $entry = RecordUser::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($entry) {
            $rec = Record::with('artist')->find($entry->record_id);
            $name = $rec->artist->name ?? '';
            $title = $rec->title ?? '';
            $entry->delete();

            return redirect('/users/' . Auth::user()->username)
                ->with('success', $name . ' - ' . $title . ' har tagits bort.');
        }

        return redirect('/users/' . Auth::user()->username);
    }

    /**
     * Update or delete a comment on a collection entry.
     */
    public function comment(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/account/login');
        }

        $id = $request->input('record');

        $entry = RecordUser::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($request->input('action') === 'delete') {
            $entry->update(['comment' => null]);
        } else {
            $request->validate([
                'comment' => 'nullable|max:255',
            ], [
                'comment.max' => 'Kommentaren får vara max :max tecken.',
            ]);

            $entry->update(['comment' => $request->input('comment')]);
        }

        return redirect('/users/' . Auth::user()->username);
    }
}
