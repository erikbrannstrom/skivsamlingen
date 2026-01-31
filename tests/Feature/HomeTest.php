<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeTest extends TestCase
{

    /**
     * Test that the homepage loads successfully.
     */
    public function test_homepage_returns_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home.index');
    }

    /**
     * Test that the about page loads successfully.
     */
    public function test_about_page_returns_successful_response(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertViewIs('home.about');
    }

    /**
     * Test that homepage shows statistics section.
     */
    public function test_homepage_shows_statistics_section(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Statistik');
        $response->assertSee('Medlemmar');
        $response->assertSee('Antal medlemmar');
    }

    /**
     * Test that homepage shows correct member count.
     */
    public function test_homepage_shows_member_count(): void
    {
        // Create some users
        User::factory()->count(3)->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        // Check that member count is displayed
        $response->assertViewHas('members', function ($members) {
            return $members['total'] === 3;
        });
    }

    /**
     * Test that homepage shows guest content for guests.
     */
    public function test_homepage_shows_guest_content(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Vad är Skivsamlingen?');
        $response->assertSee('Hur använder man Skivsamlingen?');
        $response->assertSee('Kostar det något?');
    }

    /**
     * Test that homepage shows authenticated content for logged in users.
     */
    public function test_homepage_shows_authenticated_content(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Hej Test User!');
        $response->assertDontSee('Vad är Skivsamlingen?');
    }

    /**
     * Test that homepage uses username when name is empty.
     */
    public function test_homepage_shows_username_when_name_is_empty(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'name' => null,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Hej testuser!');
    }

    /**
     * Test that homepage shows latest news.
     */
    public function test_homepage_shows_latest_news(): void
    {
        News::factory()->create([
            'title' => 'Latest News Title',
            'posted' => now(),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Senaste nytt');
        $response->assertSee('Latest News Title');
    }

    /**
     * Test that homepage shows top users list.
     */
    public function test_homepage_shows_top_users_section(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Största samlingarna');
    }

    /**
     * Test that homepage shows popular artists section.
     */
    public function test_homepage_shows_popular_artists_section(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Populäraste artisterna');
    }

    /**
     * Test that homepage shows popular albums section.
     */
    public function test_homepage_shows_popular_albums_section(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Populäraste albumen');
    }

    /**
     * Test that homepage shows latest records section.
     */
    public function test_homepage_shows_latest_records_section(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Senaste skivor');
    }

    /**
     * Test that about page shows contact information.
     */
    public function test_about_page_shows_contact_info(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('Kontakt');
        $response->assertSee('Erik Brännström');
        $response->assertSee('erik.brannstrom@skivsamlingen.se');
    }

    /**
     * Test that about page shows cookie information.
     */
    public function test_about_page_shows_cookie_info(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('Om cookies');
        $response->assertSee('Webbplatsen innehåller cookies');
    }

    /**
     * Test that about page shows history section.
     */
    public function test_about_page_shows_history(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('Varför startades Skivsamlingen?');
        $response->assertSee('Vem driver Skivsamlingen?');
    }

    /**
     * Test homepage with users who have records.
     */
    public function test_homepage_shows_top_users_with_records(): void
    {
        // Create users
        $user1 = User::factory()->create(['username' => 'bigcollector']);
        $user2 = User::factory()->create(['username' => 'smallcollector']);

        // Create artists and records
        $artistId = DB::table('artists')->insertGetId(['name' => 'Test Artist']);
        $recordId = DB::table('records')->insertGetId([
            'artist_id' => $artistId,
            'title' => 'Test Album',
            'year' => 2020,
            'format' => 'LP',
        ]);

        // Give user1 a record
        DB::table('records_users')->insert([
            'user_id' => $user1->id,
            'record_id' => $recordId,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('bigcollector');
    }
}
