<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Display a paginated list of news articles.
     */
    public function index()
    {
        $news = News::newest()->paginate(5);

        return view('news.index', [
            'news' => $news,
            'page_title' => 'Skivsamlingen - Nyheter',
        ]);
    }

    /**
     * RSS feed of the latest news articles.
     */
    public function rss()
    {
        $news = News::newest()->limit(5)->get();

        return response()
            ->view('news.rss', [
                'news' => $news,
                'feed_name' => 'Skivsamlingen',
                'feed_url' => 'https://skivsamlingen.se/',
                'page_description' => 'Skivsamlingen - musik är en livsstil.',
                'page_language' => 'sv-se',
                'creator_email' => 'erik.brannstrom@skivsamlingen.se',
            ])
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
