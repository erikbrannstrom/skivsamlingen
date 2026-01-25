<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use DatabaseTruncation;

    /**
     * Test that a user profile page loads successfully.
     */
    public function test_user_profile_returns_successful_response(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);

        $response = $this->get('/users/testuser');

        $response->assertStatus(200);
        $response->assertViewIs('users.profile');
        $response->assertSee('testuser');
    }

    /**
     * Test that non-existent user redirects to search.
     */
    public function test_nonexistent_user_shows_search(): void
    {
        $response = $this->get('/users/nonexistent');

        $response->assertStatus(200);
        $response->assertViewIs('users.search');
        $response->assertSee('nonexistent');
    }

    /**
     * Test that profile shows record count.
     */
    public function test_profile_shows_record_count(): void
    {
        $user = User::factory()->create(['username' => 'collector']);

        $artist = Artist::factory()->create(['name' => 'Test Artist']);
        $record = Record::factory()->forArtist($artist)->create([
            'title' => 'Test Album',
            'year' => 2020,
            'format' => 'LP',
        ]);
        $user->records()->attach($record->id);

        $response = $this->get('/users/collector');

        $response->assertStatus(200);
        $response->assertViewHas('num_records', 1);
    }

    /**
     * Test that profile shows records grouped by artist.
     */
    public function test_profile_shows_records_grouped_by_artist(): void
    {
        $user = User::factory()->create(['username' => 'collector']);

        $artist = Artist::factory()->create(['name' => 'The Beatles']);
        $record1 = Record::factory()->forArtist($artist)->create([
            'title' => 'Abbey Road',
            'year' => 1969,
            'format' => 'LP',
        ]);
        $record2 = Record::factory()->forArtist($artist)->create([
            'title' => 'Let It Be',
            'year' => 1970,
            'format' => 'LP',
        ]);
        $user->records()->attach([$record1->id, $record2->id]);

        $response = $this->get('/users/collector');

        $response->assertStatus(200);
        $response->assertSee('Abbey Road');
        $response->assertSee('Let It Be');
        // Check that "The" prefix is moved to end
        $response->assertSee('Beatles, The');
    }

    /**
     * Test that profile shows supporter badge for supporters.
     */
    public function test_profile_shows_supporter_badge(): void
    {
        $user = User::factory()->create(['username' => 'supporter']);

        // Add donation from within the last year
        DB::table('donations')->insert([
            'user_id' => $user->id,
            'amount' => 100,
            'donated_at' => now()->subMonth(),
        ]);

        $response = $this->get('/users/supporter');

        $response->assertStatus(200);
        $response->assertSee('Supporter');
    }

    /**
     * Test that profile doesn't show supporter badge for non-supporters.
     */
    public function test_profile_does_not_show_supporter_badge_for_non_supporters(): void
    {
        $user = User::factory()->create(['username' => 'regular']);

        $response = $this->get('/users/regular');

        $response->assertStatus(200);
        $response->assertDontSee('>Supporter<');
    }

    /**
     * Test that search returns HTML for non-AJAX requests.
     */
    public function test_search_returns_html_for_regular_requests(): void
    {
        User::factory()->create(['username' => 'searchable']);

        $response = $this->get('/users/search?q=search');

        $response->assertStatus(200);
        $response->assertViewIs('users.search');
        $response->assertSee('searchable');
    }

    /**
     * Test that search returns JSON for AJAX requests.
     */
    public function test_search_returns_json_for_ajax_requests(): void
    {
        User::factory()->create(['username' => 'ajaxuser']);

        $response = $this->getJson('/users/search?q=ajax');

        $response->assertStatus(200);
        $response->assertJsonFragment(['label' => 'ajaxuser', 'type' => 'user']);
    }

    /**
     * Test that search via POST works.
     */
    public function test_search_via_post_works(): void
    {
        User::factory()->create(['username' => 'postuser']);

        $response = $this->post('/users/search', ['query' => 'post']);

        $response->assertStatus(200);
        $response->assertViewIs('users.search');
        $response->assertSee('postuser');
    }

    /**
     * Test that AJAX search limits to 6 results plus total.
     */
    public function test_ajax_search_limits_results(): void
    {
        // Create 10 users matching the search
        for ($i = 1; $i <= 10; $i++) {
            User::factory()->create(['username' => "manyuser{$i}"]);
        }

        $response = $this->getJson('/users/search?q=manyuser');

        $response->assertStatus(200);
        $json = $response->json();
        // Should have 6 user results + 1 total entry = 7
        $this->assertCount(7, $json);
        $this->assertEquals('total', $json[6]['type']);
        $this->assertStringContainsString('10', $json[6]['label']);
    }

    /**
     * Test that export returns XML.
     */
    public function test_export_returns_xml(): void
    {
        $user = User::factory()->create(['username' => 'exporter']);

        $artist = Artist::factory()->create(['name' => 'Export Artist']);
        $record = Record::factory()->forArtist($artist)->create([
            'title' => 'Export Album',
            'year' => 2020,
            'format' => 'LP',
        ]);
        $user->records()->attach($record->id);

        $response = $this->get('/users/exporter/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $content = $response->getContent();
        $this->assertStringContainsString('<artist>Export Artist</artist>', $content);
        $this->assertStringContainsString('<title>Export Album</title>', $content);
        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-8"?>', $content);
    }

    /**
     * Test that export returns 404 for non-existent user.
     */
    public function test_export_returns_404_for_nonexistent_user(): void
    {
        $response = $this->get('/users/nonexistent/export');

        $response->assertStatus(404);
    }

    /**
     * Test that print view loads.
     */
    public function test_print_view_loads(): void
    {
        $user = User::factory()->create(['username' => 'printer']);

        $response = $this->get('/users/printer/print');

        $response->assertStatus(200);
        $response->assertViewIs('users.print');
    }

    /**
     * Test that print view returns 404 for non-existent user.
     */
    public function test_print_view_returns_404_for_nonexistent_user(): void
    {
        $response = $this->get('/users/nonexistent/print');

        $response->assertStatus(404);
    }

    /**
     * Test profile shows owner options for logged-in owner.
     */
    public function test_profile_shows_owner_options(): void
    {
        $user = User::factory()->create(['username' => 'owner']);

        $response = $this->actingAs($user)->get('/users/owner');

        $response->assertStatus(200);
        $response->assertSee('Exportera skivsamling (XML)');
        $response->assertSee('Visa utskriftsvy');
        $response->assertSee('Ändra dina uppgifter');
    }

    /**
     * Test profile doesn't show owner options for other users.
     */
    public function test_profile_hides_owner_options_for_visitors(): void
    {
        $owner = User::factory()->create(['username' => 'owner']);
        $visitor = User::factory()->create(['username' => 'visitor']);

        $response = $this->actingAs($visitor)->get('/users/owner');

        $response->assertStatus(200);
        $response->assertDontSee('Alternativ');
    }

    /**
     * Test profile pagination with offset.
     */
    public function test_profile_pagination_with_offset(): void
    {
        $user = User::factory()->create(['username' => 'paginated', 'per_page' => 5]);

        $artist = Artist::factory()->create(['name' => 'Pagination Artist']);
        for ($i = 1; $i <= 10; $i++) {
            $record = Record::factory()->forArtist($artist)->create([
                'title' => "Album {$i}",
                'year' => 2020,
                'format' => 'LP',
            ]);
            $user->records()->attach($record->id);
        }

        $response = $this->actingAs($user)->get('/users/paginated?offset=5');

        $response->assertStatus(200);
        // Should show albums 6-10 based on offset
        $response->assertViewHas('num_records', 10);
    }

    /**
     * Test profile sorting by year.
     */
    public function test_profile_sorting_by_year(): void
    {
        $user = User::factory()->create(['username' => 'sorter']);

        $artist = Artist::factory()->create(['name' => 'Sort Artist']);
        $record1 = Record::factory()->forArtist($artist)->create([
            'title' => 'Old Album',
            'year' => 1980,
            'format' => 'LP',
        ]);
        $record2 = Record::factory()->forArtist($artist)->create([
            'title' => 'New Album',
            'year' => 2020,
            'format' => 'LP',
        ]);
        $user->records()->attach([$record1->id, $record2->id]);

        $response = $this->get('/users/sorter?order=year&dir=desc');

        $response->assertStatus(200);
        // New Album should come before Old Album when sorted by year desc
        $content = $response->getContent();
        $this->assertTrue(
            strpos($content, 'New Album') < strpos($content, 'Old Album'),
            'New Album should appear before Old Album when sorted by year descending'
        );
    }

    /**
     * Test profile shows user profile information.
     */
    public function test_profile_shows_user_info(): void
    {
        $user = User::factory()->create([
            'username' => 'infuser',
            'name' => 'Info User',
            'sex' => 'm',
            'about' => 'Test about text',
            'registered' => '2020-01-15 12:00:00',
        ]);

        $response = $this->get('/users/infuser');

        $response->assertStatus(200);
        $response->assertSee('Info User');
        $response->assertSee('Man');
        $response->assertSee('Test about text');
    }

    /**
     * Test that search shows record counts.
     */
    public function test_search_shows_record_counts(): void
    {
        $user = User::factory()->create(['username' => 'countuser']);

        $artist = Artist::factory()->create(['name' => 'Count Artist']);
        $record = Record::factory()->forArtist($artist)->create([
            'title' => 'Count Album',
            'year' => 2020,
            'format' => 'LP',
        ]);
        $user->records()->attach($record->id);

        $response = $this->get('/users/search?q=countuser');

        $response->assertStatus(200);
        $response->assertSee('1 skiva');
    }

    /**
     * Test legacy URL format redirects to new query string format.
     */
    public function test_legacy_url_redirects_to_query_string_format(): void
    {
        $response = $this->get('/users/testuser/20/artist/asc');

        $response->assertRedirect('/users/testuser?offset=20&order=artist&dir=asc');
        $response->assertStatus(301);
    }

    /**
     * Test legacy URL with just offset redirects correctly.
     */
    public function test_legacy_url_with_offset_only_redirects(): void
    {
        $response = $this->get('/users/testuser/40');

        $response->assertRedirect('/users/testuser?offset=40');
        $response->assertStatus(301);
    }

    /**
     * Test legacy URL with offset and order redirects correctly.
     */
    public function test_legacy_url_with_offset_and_order_redirects(): void
    {
        $response = $this->get('/users/testuser/20/year');

        $response->assertRedirect('/users/testuser?offset=20&order=year');
        $response->assertStatus(301);
    }

    /**
     * Test that export and print URLs still work (not treated as legacy).
     */
    public function test_export_and_print_urls_not_affected_by_legacy_redirect(): void
    {
        $user = User::factory()->create(['username' => 'specialuser']);

        $exportResponse = $this->get('/users/specialuser/export');
        $exportResponse->assertStatus(200);

        $printResponse = $this->get('/users/specialuser/print');
        $printResponse->assertStatus(200);
    }
}
