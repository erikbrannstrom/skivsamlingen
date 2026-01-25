<?php

namespace Tests\Unit;

use App\Models\Artist;
use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTruncation;
    public function test_timestamps_are_disabled(): void
    {
        $user = new User();

        $this->assertFalse($user->timestamps);
    }

    public function test_fillable_contains_expected_fields(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('username', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('birth', $fillable);
        $this->assertContains('about', $fillable);
        $this->assertContains('sex', $fillable);
    }

    public function test_password_is_hidden(): void
    {
        $user = new User();
        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
    }

    public function test_casts_are_configured_correctly(): void
    {
        $user = new User();
        $casts = $user->getCasts();

        $this->assertEquals('date', $casts['birth']);
        $this->assertEquals('datetime', $casts['registered']);
        $this->assertEquals('boolean', $casts['public_email']);
        $this->assertEquals('integer', $casts['per_page']);
    }

    public function test_encrypt_password_produces_consistent_hash(): void
    {
        $username = 'testuser';
        $password = 'secret123';

        $hash1 = User::encryptPassword($username, $password);
        $hash2 = User::encryptPassword($username, $password);

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1)); // SHA256 produces 64 hex characters
    }

    public function test_encrypt_password_differs_by_username(): void
    {
        $password = 'secret123';

        $hash1 = User::encryptPassword('user1', $password);
        $hash2 = User::encryptPassword('user2', $password);

        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_verify_password_with_sha256_hash(): void
    {
        $user = new User();
        $user->username = 'testuser';
        $user->password = User::encryptPassword('testuser', 'correct');

        $this->assertTrue($user->verifyPassword('correct'));
        $this->assertFalse($user->verifyPassword('wrong'));
    }

    public function test_verify_password_with_legacy_sha1_hash(): void
    {
        $user = new User();
        $user->username = 'testuser';
        $user->password = sha1('legacypass');

        $this->assertTrue($user->verifyPassword('legacypass'));
        $this->assertFalse($user->verifyPassword('wrong'));
    }

    public function test_has_legacy_password(): void
    {
        $user = new User();
        $user->username = 'testuser';
        $user->password = sha1('legacypass');

        $this->assertTrue($user->hasLegacyPassword('legacypass'));
        $this->assertFalse($user->hasLegacyPassword('wrong'));
    }

    public function test_has_legacy_password_returns_false_for_sha256(): void
    {
        $user = new User();
        $user->username = 'testuser';
        $user->password = User::encryptPassword('testuser', 'modern');

        $this->assertFalse($user->hasLegacyPassword('modern'));
    }

    public function test_get_sex_display_returns_kvinna_for_female(): void
    {
        $user = new User(['sex' => 'f']);

        $this->assertEquals('Kvinna', $user->sex_display);
    }

    public function test_get_sex_display_returns_man_for_male(): void
    {
        $user = new User(['sex' => 'm']);

        $this->assertEquals('Man', $user->sex_display);
    }

    public function test_get_sex_display_returns_null_for_other(): void
    {
        $user = new User(['sex' => 'x']);

        $this->assertNull($user->sex_display);
    }

    public function test_get_records_count_returns_zero_for_empty_collection(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(0, $user->getRecordsCount());
    }

    public function test_get_records_count_returns_correct_count(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['name' => 'Test Artist']);

        $record1 = Record::factory()->forArtist($artist)->create(['title' => 'Album 1', 'year' => 2020, 'format' => 'LP']);
        $record2 = Record::factory()->forArtist($artist)->create(['title' => 'Album 2', 'year' => 2021, 'format' => 'CD']);

        $user->records()->attach([$record1->id, $record2->id]);

        $this->assertEquals(2, $user->getRecordsCount());
    }

    public function test_get_records_sorted_returns_records_sorted_by_artist(): void
    {
        $user = User::factory()->create();

        $artistA = Artist::factory()->create(['name' => 'ABBA']);
        $artistZ = Artist::factory()->create(['name' => 'ZZ Top']);

        $recordA = Record::factory()->forArtist($artistA)->create(['title' => 'Album A', 'year' => 2020, 'format' => 'LP']);
        $recordZ = Record::factory()->forArtist($artistZ)->create(['title' => 'Album Z', 'year' => 2020, 'format' => 'LP']);

        $user->records()->attach([$recordZ->id, $recordA->id]);

        $records = $user->getRecordsSorted('artist', 'asc');

        $this->assertCount(2, $records);
        $this->assertEquals('ABBA', $records[0]->artist_name);
        $this->assertEquals('ZZ Top', $records[1]->artist_name);
    }

    public function test_get_records_sorted_handles_the_prefix(): void
    {
        $user = User::factory()->create();

        $artistBeatles = Artist::factory()->create(['name' => 'The Beatles']);
        $artistDeportees = Artist::factory()->create(['name' => 'Deportees']);

        $recordBeatles = Record::factory()->forArtist($artistBeatles)->create(['title' => 'Abbey Road', 'year' => 1969, 'format' => 'LP']);
        $recordDeportees = Record::factory()->forArtist($artistDeportees)->create(['title' => 'Island & Shores', 'year' => 2011, 'format' => 'LP']);

        $user->records()->attach([$recordBeatles->id, $recordDeportees->id]);

        $records = $user->getRecordsSorted('artist', 'asc');

        // "The Beatles" (sorted as "Beatles") should come before "Deportees"
        $this->assertEquals('The Beatles', $records[0]->artist_name);
        $this->assertEquals('Deportees', $records[1]->artist_name);
    }

    public function test_get_records_sorted_by_year(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['name' => 'Test Artist']);

        $recordOld = Record::factory()->forArtist($artist)->create(['title' => 'Old Album', 'year' => 1980, 'format' => 'LP']);
        $recordNew = Record::factory()->forArtist($artist)->create(['title' => 'New Album', 'year' => 2020, 'format' => 'LP']);

        $user->records()->attach([$recordNew->id, $recordOld->id]);

        $recordsAsc = $user->getRecordsSorted('year', 'asc');
        $this->assertEquals(1980, $recordsAsc[0]->year);
        $this->assertEquals(2020, $recordsAsc[1]->year);

        $recordsDesc = $user->getRecordsSorted('year', 'desc');
        $this->assertEquals(2020, $recordsDesc[0]->year);
        $this->assertEquals(1980, $recordsDesc[1]->year);
    }

    public function test_get_records_sorted_with_pagination(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['name' => 'Test Artist']);

        for ($i = 1; $i <= 5; $i++) {
            $record = Record::factory()->forArtist($artist)->create([
                'title' => "Album {$i}",
                'year' => 2020,
                'format' => 'LP',
            ]);
            $user->records()->attach($record->id);
        }

        $page1 = $user->getRecordsSorted('artist', 'asc', 2, 0);
        $this->assertCount(2, $page1);

        $page2 = $user->getRecordsSorted('artist', 'asc', 2, 2);
        $this->assertCount(2, $page2);

        $page3 = $user->getRecordsSorted('artist', 'asc', 2, 4);
        $this->assertCount(1, $page3);
    }

    public function test_get_records_sorted_includes_num_records_per_artist(): void
    {
        $user = User::factory()->create();
        $artist1 = Artist::factory()->create(['name' => 'Test Artist']);
        $artist2 = Artist::factory()->create(['name' => 'Another Artist']);

        $record1 = Record::factory()->forArtist($artist1)->create(['title' => 'Album 1', 'year' => 2020, 'format' => 'LP']);
        $record2 = Record::factory()->forArtist($artist1)->create(['title' => 'Album 2', 'year' => 2021, 'format' => 'LP']);
        $record3 = Record::factory()->forArtist($artist2)->create(['title' => 'Album 3', 'year' => 2022, 'format' => 'LP']);

        $user->records()->attach([$record1->id, $record2->id, $record3->id]);

        $records = $user->getRecordsSorted('artist', 'asc');

        $this->assertEquals(1, $records[0]->num_records);
        $this->assertEquals(2, $records[1]->num_records);
        $this->assertEquals(2, $records[2]->num_records);
    }

    public function test_get_top_artists_returns_artists_ordered_by_count(): void
    {
        $user = User::factory()->create();

        $artistPopular = Artist::factory()->create(['name' => 'Popular Artist']);
        $artistUnpopular = Artist::factory()->create(['name' => 'Unpopular Artist']);

        // 3 records by popular artist
        for ($i = 1; $i <= 3; $i++) {
            $record = Record::factory()->forArtist($artistPopular)->create([
                'title' => "Album {$i}",
                'year' => 2020,
                'format' => 'LP',
            ]);
            $user->records()->attach($record->id);
        }

        // 1 record by unpopular artist
        $record = Record::factory()->forArtist($artistUnpopular)->create([
            'title' => 'Solo Album',
            'year' => 2020,
            'format' => 'LP',
        ]);
        $user->records()->attach($record->id);

        $topArtists = $user->getTopArtists(10);

        $this->assertCount(2, $topArtists);
        $this->assertEquals('Popular Artist', $topArtists[0]->name);
        $this->assertEquals(3, $topArtists[0]->records);
        $this->assertEquals('Unpopular Artist', $topArtists[1]->name);
        $this->assertEquals(1, $topArtists[1]->records);
    }

    public function test_get_top_artists_excludes_various_artists(): void
    {
        $user = User::factory()->create();

        $artistVarious = Artist::factory()->create(['name' => 'Various']);
        $artistVA = Artist::factory()->create(['name' => 'V/A']);
        $artistReal = Artist::factory()->create(['name' => 'Real Artist']);

        foreach ([$artistVarious, $artistVA, $artistReal] as $artist) {
            $record = Record::factory()->forArtist($artist)->create([
                'title' => 'Album',
                'year' => 2020,
                'format' => 'LP',
            ]);
            $user->records()->attach($record->id);
        }

        $topArtists = $user->getTopArtists(10);

        $this->assertCount(1, $topArtists);
        $this->assertEquals('Real Artist', $topArtists[0]->name);
    }

    public function test_get_top_artists_respects_limit(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 5; $i++) {
            $artist = Artist::factory()->create(['name' => "Artist {$i}"]);
            $record = Record::factory()->forArtist($artist)->create([
                'title' => 'Album',
                'year' => 2020,
                'format' => 'LP',
            ]);
            $user->records()->attach($record->id);
        }

        $topArtists = $user->getTopArtists(3);

        $this->assertCount(3, $topArtists);
    }

    public function test_get_latest_records_returns_recent_records(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['name' => 'Test Artist']);

        $record1 = Record::factory()->forArtist($artist)->create(['title' => 'First Album', 'year' => 2020, 'format' => 'LP']);
        $record2 = Record::factory()->forArtist($artist)->create(['title' => 'Second Album', 'year' => 2021, 'format' => 'LP']);

        $user->records()->attach($record1->id);
        $user->records()->attach($record2->id);

        $latestRecords = $user->getLatestRecords(10);

        $this->assertCount(2, $latestRecords);
        // Most recent first (by records_users.id)
        $this->assertEquals('Second Album', $latestRecords[0]->title);
        $this->assertEquals('First Album', $latestRecords[1]->title);
    }

    public function test_get_latest_records_includes_artist_name(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['name' => 'Famous Artist']);

        $record = Record::factory()->forArtist($artist)->create(['title' => 'Famous Album', 'year' => 2020, 'format' => 'LP']);
        $user->records()->attach($record->id);

        $latestRecords = $user->getLatestRecords(10);

        $this->assertEquals('Famous Artist', $latestRecords[0]->name);
    }

    public function test_get_latest_records_respects_limit(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['name' => 'Test Artist']);

        for ($i = 1; $i <= 5; $i++) {
            $record = Record::factory()->forArtist($artist)->create([
                'title' => "Album {$i}",
                'year' => 2020,
                'format' => 'LP',
            ]);
            $user->records()->attach($record->id);
        }

        $latestRecords = $user->getLatestRecords(3);

        $this->assertCount(3, $latestRecords);
    }

    public function test_is_supporter_returns_true_for_recent_donation(): void
    {
        $user = User::factory()->create();

        DB::table('donations')->insert([
            'user_id' => $user->id,
            'amount' => 100,
            'donated_at' => now()->subMonth(),
        ]);

        $this->assertTrue($user->isSupporter());
    }

    public function test_is_supporter_returns_false_for_old_donation(): void
    {
        $user = User::factory()->create();

        DB::table('donations')->insert([
            'user_id' => $user->id,
            'amount' => 100,
            'donated_at' => now()->subYear()->subDay(),
        ]);

        $this->assertFalse($user->isSupporter());
    }

    public function test_is_supporter_returns_false_for_small_donation(): void
    {
        $user = User::factory()->create();

        DB::table('donations')->insert([
            'user_id' => $user->id,
            'amount' => 50,
            'donated_at' => now()->subMonth(),
        ]);

        $this->assertFalse($user->isSupporter());
    }

    public function test_search_finds_users_by_username(): void
    {
        User::factory()->create(['username' => 'johndoe']);
        User::factory()->create(['username' => 'janedoe']);
        User::factory()->create(['username' => 'bobsmith']);

        $results = User::search('doe');

        $this->assertCount(2, $results);
    }

    public function test_search_finds_users_by_name(): void
    {
        User::factory()->create(['username' => 'user1', 'name' => 'John Smith']);
        User::factory()->create(['username' => 'user2', 'name' => 'Jane Smith']);
        User::factory()->create(['username' => 'user3', 'name' => 'Bob Jones']);

        $results = User::search('Smith');

        $this->assertCount(2, $results);
    }

    public function test_search_respects_limit(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            User::factory()->create(['username' => "searchuser{$i}"]);
        }

        $results = User::search('searchuser', 5);

        $this->assertCount(5, $results);
    }

    public function test_search_orders_by_username(): void
    {
        User::factory()->create(['username' => 'zebra']);
        User::factory()->create(['username' => 'alpha']);
        User::factory()->create(['username' => 'beta']);

        $results = User::search('a');

        $this->assertEquals('alpha', $results[0]->username);
        $this->assertEquals('beta', $results[1]->username);
        $this->assertEquals('zebra', $results[2]->username);
    }
}
