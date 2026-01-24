<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Services\StatisticsService;

class HomeController extends Controller
{
    public function __construct(private StatisticsService $stats)
    {
    }

    public function index()
    {
        $statistics = $this->stats->getStatistics();
        $latestRecords = $this->stats->getLatestRecords(5);
        $news = News::orderByDesc('posted')->limit(1)->get();

        return view('home.index', [
            'page_title' => 'Skivsamlingen',
            'toplist' => $statistics['toplist'],
            'popular_artists' => $statistics['popular_artists'],
            'popular_albums' => $statistics['popular_albums'],
            'members' => $statistics['members'],
            'total_recs' => $statistics['total_recs'],
            'latest_users' => $statistics['latest_users'],
            'latest_records' => $latestRecords,
            'news' => $news,
        ]);
    }

    public function about()
    {
        return view('home.about', [
            'page_title' => 'Om Skivsamlingen',
        ]);
    }
}
