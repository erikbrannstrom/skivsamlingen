<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Record;
use App\Models\RecordUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ArtistTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Auth::logout();
    }

    public function test_artist_page_loads(): void
    {
        $artist = Artist::factory()->create(['name' => 'The Beatles']);

        $this->get('/artists/' . $artist->id)
            ->assertStatus(200)
            ->assertViewIs('artists.show');
    }

    public function test_artist_page_shows_records_with_owner_counts(): void
    {
        $artist = Artist::factory()->create(['name' => 'Radiohead']);
        $record = Record::factory()->forArtist($artist)->create(['title' => 'Kid A', 'year' => 2000]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        RecordUser::create(['user_id' => $user1->id, 'record_id' => $record->id, 'comment' => '']);
        RecordUser::create(['user_id' => $user2->id, 'record_id' => $record->id, 'comment' => '']);

        $response = $this->get('/artists/' . $artist->id);

        $response->assertStatus(200)
            ->assertSee('Kid A')
            ->assertSee('2');
    }

    public function test_artist_page_returns_404_for_missing_artist(): void
    {
        $this->get('/artists/99999')->assertStatus(404);
    }

    public function test_logged_in_user_sees_add_button_for_unowned_records(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['name' => 'ABBA']);
        Record::factory()->forArtist($artist)->create(['title' => 'Gold']);

        $this->actingAs($user)
            ->get('/artists/' . $artist->id)
            ->assertSee('add.png');
    }

    public function test_logged_in_user_sees_in_collection_for_owned_records(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['name' => 'ABBA']);
        $record = Record::factory()->forArtist($artist)->create(['title' => 'Gold']);
        RecordUser::create(['user_id' => $user->id, 'record_id' => $record->id, 'comment' => '']);

        $this->actingAs($user)
            ->get('/artists/' . $artist->id)
            ->assertSee('tick.png')
            ->assertDontSee('add.png');
    }

    public function test_guest_sees_no_action_column(): void
    {
        $artist = Artist::factory()->create(['name' => 'ABBA']);
        Record::factory()->forArtist($artist)->create(['title' => 'Gold']);

        $this->get('/artists/' . $artist->id)
            ->assertDontSee('add.png')
            ->assertDontSee('tick.png');
    }

    public function test_add_to_collection_requires_auth(): void
    {
        $artist = Artist::factory()->create(['name' => 'Test']);
        $record = Record::factory()->forArtist($artist)->create();

        $this->post('/collection/add', ['record_id' => $record->id])
            ->assertRedirect('/account/login');
    }

    public function test_add_to_collection_creates_record_user(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['name' => 'Test']);
        $record = Record::factory()->forArtist($artist)->create();

        $this->actingAs($user)
            ->post('/collection/add', ['record_id' => $record->id])
            ->assertRedirect('/artists/' . $artist->id)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('records_users', [
            'user_id' => $user->id,
            'record_id' => $record->id,
        ]);
    }

    public function test_add_to_collection_is_idempotent(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['name' => 'Test']);
        $record = Record::factory()->forArtist($artist)->create();
        RecordUser::create(['user_id' => $user->id, 'record_id' => $record->id, 'comment' => '']);

        $this->actingAs($user)
            ->post('/collection/add', ['record_id' => $record->id]);

        $this->assertEquals(1, RecordUser::where('user_id', $user->id)
            ->where('record_id', $record->id)
            ->count());
    }

    public function test_add_to_collection_redirects_to_artist_page(): void
    {
        $user = User::factory()->create();
        $artist = Artist::factory()->create(['name' => 'Test']);
        $record = Record::factory()->forArtist($artist)->create();

        $this->actingAs($user)
            ->post('/collection/add', ['record_id' => $record->id])
            ->assertRedirect('/artists/' . $artist->id);
    }
}
