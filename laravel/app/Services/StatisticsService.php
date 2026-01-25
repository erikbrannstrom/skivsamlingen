<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * Get top users by collection size.
     */
    public function getTopUsers(int $limit = 10): Collection
    {
      $topUserIds = DB::table('records_users')
          ->select('user_id', DB::raw('COUNT(*) AS recs'))
          ->groupBy('user_id')
          ->orderByDesc('recs')
          ->limit($limit);

      return DB::table(DB::raw("({$topUserIds->toSql()}) as top"))
          ->mergeBindings($topUserIds)
          ->join('users', 'users.id', '=', 'top.user_id')
          ->select('users.username', 'top.recs')
          ->orderByDesc('top.recs')
          ->get();
    }

    /**
     * Get most popular artists by total records in collections.
     */
    public function getTopArtists(int $limit = 10): Collection
    {
        $excludedArtistIds = DB::table('artists')
            ->whereIn('name', ['Various', 'V/A'])
            ->pluck('id');

        $topArtists = DB::table('records_users')
            ->join('records', 'records.id', '=', 'records_users.record_id')
            ->select('records.artist_id', DB::raw('COUNT(*) AS records'))
            ->whereNotIn('records.artist_id', $excludedArtistIds)
            ->groupBy('records.artist_id')
            ->orderByDesc('records')
            ->limit($limit);

        // Join only the top 10 to get names
        return DB::table(DB::raw("({$topArtists->toSql()}) as top"))
            ->mergeBindings($topArtists)
            ->join('artists', 'artists.id', '=', 'top.artist_id')
            ->select('artists.name', 'top.records')
            ->orderByDesc('top.records')
            ->orderBy('artists.name')
            ->get();
    }

    /**
     * Get most popular albums by number of owners.
     */
    public function getPopularAlbums(int $limit = 10): Collection
    {
        $topRecords = DB::table('records_users')
            ->select('record_id', DB::raw('COUNT(*) AS records'))
            ->groupBy('record_id')
            ->orderByDesc('records')
            ->limit($limit);

        return DB::table(DB::raw("({$topRecords->toSql()}) as top"))
            ->mergeBindings($topRecords)
            ->select('records.title', 'artists.name', 'top.records')
            ->join('records', 'records.id', '=', 'top.record_id')
            ->join('artists', 'artists.id', '=', 'records.artist_id')
            ->orderByDesc('top.records')
            ->orderBy('artists.name')
            ->orderBy('records.title')
            ->get();
    }

    /**
     * Get member statistics (total, this week, last week).
     */
    public function getMemberStats(): array
    {
        return [
            'total' => DB::table('users')->count(),
            'this_week' => DB::table('users')
                ->whereRaw('YEARWEEK(registered, 3) = YEARWEEK(NOW(), 3)')
                ->count(),
            'last_week' => DB::table('users')
                ->whereRaw('YEARWEEK(registered, 3) = YEARWEEK(NOW(), 3) - 1')
                ->count(),
        ];
    }

    /**
     * Get total number of records across all collections.
     */
    public function getTotalRecords(): int
    {
        return DB::table('records_users')->count();
    }

    /**
     * Get newly registered users.
     */
    public function getNewUsers(int $limit = 5): Collection
    {
        return DB::table('users')
            ->select('username', 'registered')
            ->orderByDesc('registered')
            ->limit($limit)
            ->get();
    }

    /**
     * Get latest records added to any collection.
     */
    public function getLatestRecords(int $limit = 5): Collection
    {
        return DB::table('records_users')
            ->select('users.username', 'records.title', 'artists.name')
            ->join('records', 'records.id', '=', 'records_users.record_id')
            ->join('artists', 'artists.id', '=', 'records.artist_id')
            ->join('users', 'users.id', '=', 'records_users.user_id')
            ->orderByDesc('records.id')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all homepage statistics.
     */
    public function getStatistics(): array
    {
        return [
            'toplist' => $this->getTopUsers(),
            'popular_artists' => $this->getTopArtists(),
            'popular_albums' => $this->getPopularAlbums(),
            'members' => $this->getMemberStats(),
            'total_recs' => $this->getTotalRecords(),
            'latest_users' => $this->getNewUsers(),
        ];
    }
}
