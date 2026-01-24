<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use DatabaseTruncation;

    /**
     * Test that the news index page loads successfully.
     */
    public function test_news_index_returns_successful_response(): void
    {
        $response = $this->get('/news');

        $response->assertStatus(200);
        $response->assertViewIs('news.index');
    }

    /**
     * Test that news articles are displayed on the index page.
     */
    public function test_news_index_displays_news_articles(): void
    {
        $news = News::factory()->count(3)->create();

        $response = $this->get('/news');

        $response->assertStatus(200);
        foreach ($news as $article) {
            $response->assertSee($article->title);
        }
    }

    /**
     * Test that news pagination works correctly.
     */
    public function test_news_index_paginates_results(): void
    {
        // Create more than one page of news (5 per page)
        News::factory()->count(7)->create();

        $response = $this->get('/news');

        $response->assertStatus(200);
        // Should only show 5 items on first page
        $response->assertViewHas('news', function ($news) {
            return $news->count() === 5;
        });
    }

    /**
     * Test that second page of pagination works.
     */
    public function test_news_index_second_page(): void
    {
        // Create more than one page of news
        News::factory()->count(7)->create();

        $response = $this->get('/news?page=2');

        $response->assertStatus(200);
        // Should show remaining 2 items on second page
        $response->assertViewHas('news', function ($news) {
            return $news->count() === 2;
        });
    }

    /**
     * Test that news index handles empty state.
     */
    public function test_news_index_handles_empty_state(): void
    {
        $response = $this->get('/news');

        $response->assertStatus(200);
        $response->assertSee('Inga nyheter att visa.');
    }

    /**
     * Test that RSS feed returns valid XML.
     */
    public function test_rss_feed_returns_xml(): void
    {
        News::factory()->count(3)->create();

        $response = $this->get('/news/rss');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Test that RSS feed contains news items.
     */
    public function test_rss_feed_contains_news_items(): void
    {
        News::factory()->count(3)->create();

        $response = $this->get('/news/rss');

        $response->assertStatus(200);

        // Check that the RSS contains item elements
        $content = $response->getContent();
        $this->assertStringContainsString('<item>', $content);
        $this->assertStringContainsString('</item>', $content);

        // Check feed metadata
        $this->assertStringContainsString('<title>Skivsamlingen</title>', $content);
        $this->assertStringContainsString('https://skivsamlingen.se/', $content);
    }

    /**
     * Test that RSS feed limits to 5 items.
     */
    public function test_rss_feed_limits_to_five_items(): void
    {
        News::factory()->count(10)->create();

        $response = $this->get('/news/rss');

        $response->assertStatus(200);

        $content = $response->getContent();
        $itemCount = substr_count($content, '<item>');
        $this->assertEquals(5, $itemCount);
    }

    /**
     * Test that RSS feed is valid XML.
     */
    public function test_rss_feed_is_valid_xml(): void
    {
        News::factory()->count(3)->create();

        $response = $this->get('/news/rss');

        $response->assertStatus(200);

        // Parse as XML - will throw if invalid
        $content = $response->getContent();
        $xml = simplexml_load_string($content);

        $this->assertNotFalse($xml, 'RSS feed should be valid XML');
        $this->assertEquals('rss', $xml->getName());
    }

    /**
     * Test that news are ordered by posted date descending.
     */
    public function test_news_ordered_by_posted_date_descending(): void
    {
        $oldNews = News::factory()->create([
            'posted' => now()->subDay(),
            'title' => 'Old News',
        ]);
        $newNews = News::factory()->create([
            'posted' => now(),
            'title' => 'New News',
        ]);

        $response = $this->get('/news');

        $response->assertStatus(200);

        // New news should appear before old news
        $content = $response->getContent();
        $newPos = strpos($content, 'New News');
        $oldPos = strpos($content, 'Old News');

        $this->assertNotFalse($newPos, 'New news should be visible on page');
        $this->assertNotFalse($oldPos, 'Old news should be visible on page');
        $this->assertLessThan($oldPos, $newPos, 'Newer news should appear before older news');
    }
}
