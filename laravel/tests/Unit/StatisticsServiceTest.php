<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{

    private StatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StatisticsService();
    }

    /**
     * Helper to create a user with records.
     */
    private function createUserWithRecords(string $username, int $recordCount): User
    {
        $user = User::factory()->create(['username' => $username]);

        for ($i = 0; $i < $recordCount; $i++) {
            $artistId = DB::table('artists')->insertGetId(['name' => "Artist {$username} {$i}"]);
            $recordId = DB::table('records')->insertGetId([
                'artist_id' => $artistId,
                'title' => "Album {$username} {$i}",
                'year' => 2020,
                'format' => 'LP',
            ]);
            DB::table('records_users')->insert([
                'user_id' => $user->id,
                'record_id' => $recordId,
            ]);
        }

        return $user;
    }

    // ==================== getTopUsers tests ====================

    public function test_get_top_users_returns_users_ordered_by_collection_size(): void
    {
        $this->createUserWithRecords('smallcollector', 5);
        $this->createUserWithRecords('bigcollector', 20);
        $this->createUserWithRecords('mediumcollector', 10);
        $this->createUserWithRecords('norecollector', 0);

        $result = $this->service->getTopUsers(10);

        $this->assertCount(3, $result);
        $this->assertEquals('bigcollector', $result[0]->username);
        $this->assertEquals(20, $result[0]->recs);
        $this->assertEquals('mediumcollector', $result[1]->username);
        $this->assertEquals('smallcollector', $result[2]->username);
        $this->assertFalse($result->contains('username', 'norecollector'));
    }

    public function test_get_top_users_respects_limit(): void
    {
        $this->createUserWithRecords('user1', 10);
        $this->createUserWithRecords('user2', 8);
        $this->createUserWithRecords('user3', 6);

        $result = $this->service->getTopUsers(2);

        $this->assertCount(2, $result);
    }

    public function test_get_top_users_returns_empty_collection_when_no_records(): void
    {
        User::factory()->create();

        $result = $this->service->getTopUsers();

        $this->assertCount(0, $result);
    }

    // ==================== getTopArtists tests ====================

    public function test_get_top_artists_returns_artists_ordered_by_record_count(): void
    {
        $user = User::factory()->create();

        // Create artists with different popularity
        $popularArtist = DB::table('artists')->insertGetId(['name' => 'Popular Artist']);
        $unpopularArtist = DB::table('artists')->insertGetId(['name' => 'Unpopular Artist']);

        // Add 3 records for popular artist
        for ($i = 0; $i < 3; $i++) {
            $recordId = DB::table('records')->insertGetId([
                'artist_id' => $popularArtist,
                'title' => "Album {$i}",
            ]);
            DB::table('records_users')->insert([
                'user_id' => $user->id,
                'record_id' => $recordId,
            ]);
        }

        // Add 1 record for unpopular artist
        $recordId = DB::table('records')->insertGetId([
            'artist_id' => $unpopularArtist,
            'title' => 'Only Album',
        ]);
        DB::table('records_users')->insert([
            'user_id' => $user->id,
            'record_id' => $recordId,
        ]);

        $result = $this->service->getTopArtists(10);

        $this->assertCount(2, $result);
        $this->assertEquals('Popular Artist', $result[0]->name);
        $this->assertEquals(3, $result[0]->records);
        $this->assertEquals('Unpopular Artist', $result[1]->name);
    }

    public function test_get_top_artists_excludes_various_artists(): void
    {
        $user = User::factory()->create();

        $variousId = DB::table('artists')->insertGetId(['name' => 'Various']);
        $vaId = DB::table('artists')->insertGetId(['name' => 'V/A']);
        $realArtist = DB::table('artists')->insertGetId(['name' => 'Real Artist']);

        foreach ([$variousId, $vaId, $realArtist] as $artistId) {
            $recordId = DB::table('records')->insertGetId([
                'artist_id' => $artistId,
                'title' => 'Some Album',
            ]);
            DB::table('records_users')->insert([
                'user_id' => $user->id,
                'record_id' => $recordId,
            ]);
        }

        $result = $this->service->getTopArtists(10);

        $this->assertCount(1, $result);
        $this->assertEquals('Real Artist', $result[0]->name);
    }

    public function test_get_top_artists_respects_limit(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $artistId = DB::table('artists')->insertGetId(['name' => "Artist {$i}"]);
            $recordId = DB::table('records')->insertGetId([
                'artist_id' => $artistId,
                'title' => 'Album',
            ]);
            DB::table('records_users')->insert([
                'user_id' => $user->id,
                'record_id' => $recordId,
            ]);
        }

        $result = $this->service->getTopArtists(3);

        $this->assertCount(3, $result);
    }

    // ==================== getPopularAlbums tests ====================

    public function test_get_popular_albums_returns_albums_ordered_by_owner_count(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $artistId = DB::table('artists')->insertGetId(['name' => 'Test Artist']);

        // Popular album owned by 3 users
        $popularAlbum = DB::table('records')->insertGetId([
            'artist_id' => $artistId,
            'title' => 'Popular Album',
        ]);

        // Unpopular album owned by 1 user
        $unpopularAlbum = DB::table('records')->insertGetId([
            'artist_id' => $artistId,
            'title' => 'Unpopular Album',
        ]);

        foreach ([$user1, $user2, $user3] as $user) {
            DB::table('records_users')->insert([
                'user_id' => $user->id,
                'record_id' => $popularAlbum,
            ]);
        }

        DB::table('records_users')->insert([
            'user_id' => $user1->id,
            'record_id' => $unpopularAlbum,
        ]);

        $result = $this->service->getPopularAlbums(10);

        $this->assertCount(2, $result);
        $this->assertEquals('Popular Album', $result[0]->title);
        $this->assertEquals(3, $result[0]->records);
        $this->assertEquals('Unpopular Album', $result[1]->title);
        $this->assertEquals(1, $result[1]->records);
    }

    public function test_get_popular_albums_includes_artist_name(): void
    {
        $user = User::factory()->create();
        $artistId = DB::table('artists')->insertGetId(['name' => 'The Beatles']);
        $recordId = DB::table('records')->insertGetId([
            'artist_id' => $artistId,
            'title' => 'Abbey Road',
        ]);
        DB::table('records_users')->insert([
            'user_id' => $user->id,
            'record_id' => $recordId,
        ]);

        $result = $this->service->getPopularAlbums(10);

        $this->assertEquals('The Beatles', $result[0]->name);
        $this->assertEquals('Abbey Road', $result[0]->title);
    }

    // ==================== getMemberStats tests ====================

    public function test_get_member_stats_returns_total_count(): void
    {
        User::factory()->count(5)->create();

        $result = $this->service->getMemberStats();

        $this->assertEquals(5, $result['total']);
    }

    public function test_get_member_stats_returns_this_week_count(): void
    {
        // User registered this week
        User::factory()->create(['registered' => now()]);

        // User registered last month
        User::factory()->create(['registered' => now()->subMonth()]);

        $result = $this->service->getMemberStats();

        $this->assertEquals(1, $result['this_week']);
        $this->assertEquals(2, $result['total']);
    }

    public function test_get_member_stats_returns_last_week_count(): void
    {
        // User registered last week
        User::factory()->create(['registered' => now()->subWeek()]);

        // User registered this week
        User::factory()->create(['registered' => now()]);

        $result = $this->service->getMemberStats();

        $this->assertEquals(1, $result['last_week']);
    }

    public function test_get_member_stats_returns_zeros_when_no_users(): void
    {
        $result = $this->service->getMemberStats();

        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['this_week']);
        $this->assertEquals(0, $result['last_week']);
    }

    // ==================== getTotalRecords tests ====================

    public function test_get_total_records_returns_count_of_all_collection_entries(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $artistId = DB::table('artists')->insertGetId(['name' => 'Artist']);
        $recordId = DB::table('records')->insertGetId([
            'artist_id' => $artistId,
            'title' => 'Album',
        ]);

        // Same record in two collections = 2 entries
        DB::table('records_users')->insert(['user_id' => $user1->id, 'record_id' => $recordId]);
        DB::table('records_users')->insert(['user_id' => $user2->id, 'record_id' => $recordId]);

        $result = $this->service->getTotalRecords();

        $this->assertEquals(2, $result);
    }

    public function test_get_total_records_returns_zero_when_no_records(): void
    {
        $result = $this->service->getTotalRecords();

        $this->assertEquals(0, $result);
    }

    // ==================== getNewUsers tests ====================

    public function test_get_new_users_returns_users_ordered_by_registration_date(): void
    {
        User::factory()->create([
            'username' => 'oldest',
            'registered' => now()->subDays(10),
        ]);
        User::factory()->create([
            'username' => 'newest',
            'registered' => now(),
        ]);
        User::factory()->create([
            'username' => 'middle',
            'registered' => now()->subDays(5),
        ]);

        $result = $this->service->getNewUsers(10);

        $this->assertCount(3, $result);
        $this->assertEquals('newest', $result[0]->username);
        $this->assertEquals('middle', $result[1]->username);
        $this->assertEquals('oldest', $result[2]->username);
    }

    public function test_get_new_users_respects_limit(): void
    {
        User::factory()->count(10)->create();

        $result = $this->service->getNewUsers(3);

        $this->assertCount(3, $result);
    }

    public function test_get_new_users_includes_registration_date(): void
    {
        $registeredAt = now()->subDay();
        User::factory()->create([
            'username' => 'testuser',
            'registered' => $registeredAt,
        ]);

        $result = $this->service->getNewUsers(1);

        $this->assertNotNull($result[0]->registered);
    }

    // ==================== getLatestRecords tests ====================

    public function test_get_latest_records_returns_records_ordered_by_id_desc(): void
    {
        $user = User::factory()->create(['username' => 'collector']);
        $artistId = DB::table('artists')->insertGetId(['name' => 'Test Artist']);

        $record1 = DB::table('records')->insertGetId([
            'artist_id' => $artistId,
            'title' => 'First Album',
        ]);
        $record2 = DB::table('records')->insertGetId([
            'artist_id' => $artistId,
            'title' => 'Second Album',
        ]);

        DB::table('records_users')->insert(['user_id' => $user->id, 'record_id' => $record1]);
        DB::table('records_users')->insert(['user_id' => $user->id, 'record_id' => $record2]);

        $result = $this->service->getLatestRecords(10);

        $this->assertCount(2, $result);
        $this->assertEquals('Second Album', $result[0]->title);
        $this->assertEquals('First Album', $result[1]->title);
    }

    public function test_get_latest_records_includes_username_and_artist(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $artistId = DB::table('artists')->insertGetId(['name' => 'The Beatles']);
        $recordId = DB::table('records')->insertGetId([
            'artist_id' => $artistId,
            'title' => 'Abbey Road',
        ]);
        DB::table('records_users')->insert(['user_id' => $user->id, 'record_id' => $recordId]);

        $result = $this->service->getLatestRecords(1);

        $this->assertEquals('testuser', $result[0]->username);
        $this->assertEquals('The Beatles', $result[0]->name);
        $this->assertEquals('Abbey Road', $result[0]->title);
    }

    public function test_get_latest_records_respects_limit(): void
    {
        $user = User::factory()->create();
        $artistId = DB::table('artists')->insertGetId(['name' => 'Artist']);

        for ($i = 0; $i < 10; $i++) {
            $recordId = DB::table('records')->insertGetId([
                'artist_id' => $artistId,
                'title' => "Album {$i}",
            ]);
            DB::table('records_users')->insert(['user_id' => $user->id, 'record_id' => $recordId]);
        }

        $result = $this->service->getLatestRecords(5);

        $this->assertCount(5, $result);
    }

    // ==================== getStatistics tests ====================

    public function test_get_statistics_returns_all_statistics(): void
    {
        $this->createUserWithRecords('testuser', 5);

        $result = $this->service->getStatistics();

        $this->assertArrayHasKey('toplist', $result);
        $this->assertArrayHasKey('popular_artists', $result);
        $this->assertArrayHasKey('popular_albums', $result);
        $this->assertArrayHasKey('members', $result);
        $this->assertArrayHasKey('total_recs', $result);
        $this->assertArrayHasKey('latest_users', $result);
    }

    public function test_get_statistics_returns_correct_data_types(): void
    {
        $result = $this->service->getStatistics();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['toplist']);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['popular_artists']);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['popular_albums']);
        $this->assertIsArray($result['members']);
        $this->assertIsInt($result['total_recs']);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['latest_users']);
    }
}
