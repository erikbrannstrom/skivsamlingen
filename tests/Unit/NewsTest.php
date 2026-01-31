<?php

namespace Tests\Unit;

use App\Models\News;
use PHPUnit\Framework\TestCase;

class NewsTest extends TestCase
{
    public function test_timestamps_are_disabled(): void
    {
        $news = new News();

        $this->assertFalse($news->timestamps);
    }

    public function test_table_name_is_news(): void
    {
        $news = new News();

        $this->assertEquals('news', $news->getTable());
    }

    public function test_fillable_contains_expected_fields(): void
    {
        $news = new News();
        $fillable = $news->getFillable();

        $this->assertContains('title', $fillable);
        $this->assertContains('body', $fillable);
        $this->assertContains('posted', $fillable);
    }

    public function test_posted_is_cast_to_datetime(): void
    {
        $news = new News();
        $casts = $news->getCasts();

        $this->assertArrayHasKey('posted', $casts);
        $this->assertEquals('datetime', $casts['posted']);
    }
}
