<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UsersController extends Controller
{
    /**
     * Display a user's profile and record collection.
     */
    public function profile(Request $request, string $username): View
    {
        $user = User::where('username', $username)->first();

        if (! $user) {
            return $this->search($request->merge(['q' => $username]));
        }

        $offset = (int) $request->input('offset', 0);
        $order = $request->input('order', 'artist');
        $direction = $request->input('dir', 'asc');

        // Validate direction
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        // Validate order field
        $validOrders = ['artist', 'format', 'year'];
        if (! in_array($order, $validOrders)) {
            $order = 'artist';
        }

        $perPage = Auth::check() ? Auth::user()->per_page : 20;
        if (! $perPage || $perPage < 1) {
            $perPage = 20;
        }

        $numRecords = $user->getRecordsCount();

        // Get records using Eloquent
        $records = $user->getRecordsSorted($order, $direction, $perPage, $offset);

        // Calculate pagination data
        $totalPages = $numRecords > 0 ? (int) ceil($numRecords / $perPage) : 0;
        $currentPage = (int) floor($offset / $perPage) + 1;

        // Get sidebar data
        $numForTopArtists = $numRecords >= 25 ? 10 : 5;
        $topArtists = $user->getTopArtists($numForTopArtists);
        $latestRecords = $user->getLatestRecords($numForTopArtists);

        return view('users.profile', [
            'page_title' => 'Skivsamlingen - ' . $user->username,
            'user' => $user,
            'records' => $records,
            'num_records' => $numRecords,
            'top_artists' => $topArtists,
            'latest_records' => $latestRecords,
            'is_supporter' => $user->isSupporter(),
            // Pagination data for components
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'offset' => $offset,
            'order' => $order,
            'direction' => $direction,
        ]);
    }

    /**
     * Search for users.
     */
    public function search(Request $request): View|JsonResponse
    {
        $query = $request->input('q') ?? $request->input('query');

        if (! $query) {
            return redirect('/');
        }

        $users = User::search($query);

        if ($request->ajax() || $request->wantsJson()) {
            $result = [];
            $i = 0;
            foreach ($users as $user) {
                $i++;
                $result[] = ['label' => $user->username, 'type' => 'user'];
                if ($i >= 6) {
                    break;
                }
            }

            $count = $users->count();
            if ($count === 0) {
                $text = 'Inga resultat.';
            } else {
                $text = ($count <= 6 ? 'Visar' : 'Visa') . ' alla ' . $count . ' resultat..';
            }
            $result[] = ['label' => $text, 'type' => 'total'];

            return response()->json($result);
        }

        // Add record count for each user in HTML view
        foreach ($users as $user) {
            $user->num_records = $user->getRecordsCount();
        }

        return view('users.search', [
            'page_title' => 'Sökresultat - ' . $query,
            'query' => $query,
            'users' => $users,
        ]);
    }

    /**
     * Export user's collection as XML.
     */
    public function export(string $username): Response
    {
        $user = User::where('username', $username)->firstOrFail();

        $records = $user->getRecordsSorted('artist', 'asc');

        $filename = 'skivsamling-' . date('Ymd') . '.xml';

        return response()
            ->view('users.export', [
                'username' => $user->username,
                'records' => $records,
            ])
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Display printable view of user's collection.
     */
    public function printview(string $username): View
    {
        $user = User::where('username', $username)->firstOrFail();

        $records = $user->getRecordsSorted('artist', 'asc');

        return view('users.print', [
            'page_title' => 'Skivsamlingen - ' . $user->username,
            'user' => $user,
            'records' => $records,
            'num_records' => $user->getRecordsCount(),
        ]);
    }
}
