<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Record;
use App\Models\RecordUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CollectionTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Auth::logout();
    }

    // ===================
    // AUTH REQUIRED TESTS
    // ===================

    public function test_add_record_requires_auth(): void
    {
        $this->get('/collection/record')->assertRedirect('/account/login');
    }

    public function test_store_record_requires_auth(): void
    {
        $this->post('/collection/record', [
            'artist' => 'Test',
            'title' => 'Test',
        ])->assertRedirect('/account/login');
    }

    public function test_delete_record_requires_auth(): void
    {
        $this->get('/collection/delete/1')->assertRedirect('/account/login');
    }

    public function test_destroy_record_requires_auth(): void
    {
        $this->post('/collection/delete', ['record' => 1])->assertRedirect('/account/login');
    }

    public function test_comment_requires_auth(): void
    {
        $this->post('/collection/comment', ['record' => 1])->assertRedirect('/account/login');
    }

    // ===================
    // ADD RECORD TESTS
    // ===================

    public function test_add_record_form_loads(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/collection/record')
            ->assertStatus(200)
            ->assertViewIs('collection.record')
            ->assertSee('Ny skiva');
    }

    public function test_add_new_record_creates_artist_and_record(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/collection/record', [
            'artist' => 'The Beatles',
            'title' => 'Abbey Road',
            'year' => '1969',
            'format' => 'LP',
        ]);

        $response->assertRedirect('/collection/record');
        $response->assertSessionHas('success');

        $this->assertNotNull(Artist::where('name', 'The Beatles')->first());
        $record = Record::where('title', 'Abbey Road')->first();
        $this->assertNotNull($record);
        $this->assertEquals(1969, $record->year);
        $this->assertEquals('LP', $record->format);
        $this->assertNotNull(RecordUser::where('user_id', $user->id)->first());
    }

    public function test_add_record_with_comment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/collection/record', [
            'artist' => 'Radiohead',
            'title' => 'Kid A',
            'comment' => 'Fantastisk skiva!',
        ]);

        $entry = RecordUser::where('user_id', $user->id)->first();
        $this->assertEquals('Fantastisk skiva!', $entry->comment);
    }

    public function test_add_record_reuses_existing_artist(): void
    {
        $user = User::factory()->create();
        $artist = Artist::create(['name' => 'U2']);

        $this->actingAs($user)->post('/collection/record', [
            'artist' => 'U2',
            'title' => 'Achtung Baby',
        ]);

        $this->assertEquals(1, Artist::where('name', 'U2')->count());
    }

    public function test_add_record_reuses_existing_record(): void
    {
        $user = User::factory()->create();
        $artist = Artist::create(['name' => 'U2']);
        $record = Record::create([
            'artist_id' => $artist->id,
            'title' => 'Achtung Baby',
            'year' => null,
            'format' => null,
        ]);

        $this->actingAs($user)->post('/collection/record', [
            'artist' => 'U2',
            'title' => 'Achtung Baby',
        ]);

        $this->assertEquals(1, Record::where('title', 'Achtung Baby')->count());
        $entry = RecordUser::where('user_id', $user->id)->first();
        $this->assertNotNull($entry);
        $this->assertEquals($record->id, $entry->record_id);
    }

    // ===================
    // CASE SENSITIVITY
    // ===================

    public function test_case_sensitive_title_matching(): void
    {
        $user = User::factory()->create();
        $artist = Artist::create(['name' => 'Test']);
        Record::create([
            'artist_id' => $artist->id,
            'title' => 'abc',
            'year' => null,
            'format' => null,
        ]);

        $this->actingAs($user)->post('/collection/record', [
            'artist' => 'Test',
            'title' => 'ABC',
        ]);

        // Should create a new record because case differs
        $this->assertEquals(2, Record::where('artist_id', $artist->id)->count());
    }

    // ===================
    // EDIT RECORD TESTS
    // ===================

    public function test_edit_record_form_loads(): void
    {
        $user = User::factory()->create();
        $artist = Artist::create(['name' => 'Nirvana']);
        $record = Record::create(['artist_id' => $artist->id, 'title' => 'Nevermind', 'year' => 1991, 'format' => 'CD']);
        $entry = RecordUser::create(['user_id' => $user->id, 'record_id' => $record->id]);

        $this->actingAs($user)
            ->get('/collection/record/' . $entry->id)
            ->assertStatus(200)
            ->assertSee('Redigera skiva')
            ->assertSee('Nirvana')
            ->assertSee('Nevermind');
    }

    public function test_edit_record_preserves_comment(): void
    {
        $user = User::factory()->create();
        $artist = Artist::create(['name' => 'Nirvana']);
        $record = Record::create(['artist_id' => $artist->id, 'title' => 'Nevermind', 'year' => 1991, 'format' => 'CD']);
        $entry = RecordUser::create(['user_id' => $user->id, 'record_id' => $record->id, 'comment' => 'Bra skiva']);

        $response = $this->actingAs($user)->post('/collection/record/' . $entry->id, [
            'artist' => 'Nirvana',
            'title' => 'In Utero',
            'year' => '1993',
            'format' => 'CD',
        ]);

        $response->assertRedirect('/users/' . $user->username);

        $newEntry = RecordUser::where('user_id', $user->id)->first();
        $this->assertEquals('Bra skiva', $newEntry->comment);
    }

    public function test_edit_record_by_other_user_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $artist = Artist::create(['name' => 'Test']);
        $record = Record::create(['artist_id' => $artist->id, 'title' => 'Test', 'year' => null, 'format' => null]);
        $entry = RecordUser::create(['user_id' => $user1->id, 'record_id' => $record->id]);

        $this->actingAs($user2)
            ->get('/collection/record/' . $entry->id)
            ->assertStatus(404);
    }

    // ===================
    // DELETE RECORD TESTS
    // ===================

    public function test_delete_confirmation_page_loads(): void
    {
        $user = User::factory()->create();
        $artist = Artist::create(['name' => 'ABBA']);
        $record = Record::create(['artist_id' => $artist->id, 'title' => 'Gold', 'year' => null, 'format' => null]);
        $entry = RecordUser::create(['user_id' => $user->id, 'record_id' => $record->id]);

        $this->actingAs($user)
            ->get('/collection/delete/' . $entry->id)
            ->assertStatus(200)
            ->assertSee('Ta bort skiva')
            ->assertSee('ABBA')
            ->assertSee('Gold');
    }

    public function test_delete_record(): void
    {
        $user = User::factory()->create();
        $artist = Artist::create(['name' => 'ABBA']);
        $record = Record::create(['artist_id' => $artist->id, 'title' => 'Gold', 'year' => null, 'format' => null]);
        $entry = RecordUser::create(['user_id' => $user->id, 'record_id' => $record->id]);

        $response = $this->actingAs($user)->post('/collection/delete', [
            'record' => $entry->id,
        ]);

        $response->assertRedirect('/users/' . $user->username);
        $response->assertSessionHas('success');
        $this->assertNull(RecordUser::find($entry->id));
    }

    // ===================
    // COMMENT TESTS
    // ===================

    public function test_add_comment(): void
    {
        $user = User::factory()->create();
        $artist = Artist::create(['name' => 'Kent']);
        $record = Record::create(['artist_id' => $artist->id, 'title' => 'Isola', 'year' => null, 'format' => null]);
        $entry = RecordUser::create(['user_id' => $user->id, 'record_id' => $record->id]);

        $this->actingAs($user)->post('/collection/comment', [
            'record' => $entry->id,
            'action' => 'edit',
            'comment' => 'Svensk klassiker',
        ]);

        $entry->refresh();
        $this->assertEquals('Svensk klassiker', $entry->comment);
    }

    public function test_update_comment(): void
    {
        $user = User::factory()->create();
        $artist = Artist::create(['name' => 'Kent']);
        $record = Record::create(['artist_id' => $artist->id, 'title' => 'Isola', 'year' => null, 'format' => null]);
        $entry = RecordUser::create(['user_id' => $user->id, 'record_id' => $record->id, 'comment' => 'Helt okej skiva']);

        $this->actingAs($user)
            ->get('/collection/record/' . $entry->id)
            ->assertStatus(200)
            ->assertSee('Helt okej skiva');

        $this->actingAs($user)->post('/collection/comment', [
            'record' => $entry->id,
            'action' => 'edit',
            'comment' => 'Svinbra',
        ]);

        $entry->refresh();
        $this->assertEquals('Svinbra', $entry->comment);
    }

    public function test_delete_comment(): void
    {
        $user = User::factory()->create();
        $artist = Artist::create(['name' => 'Kent']);
        $record = Record::create(['artist_id' => $artist->id, 'title' => 'Isola', 'year' => null, 'format' => null]);
        $entry = RecordUser::create(['user_id' => $user->id, 'record_id' => $record->id, 'comment' => 'En kommentar']);

        $this->actingAs($user)->post('/collection/comment', [
            'record' => $entry->id,
            'action' => 'delete',
        ]);

        $entry->refresh();
        $this->assertNull($entry->comment);
    }

    // ===================
    // VALIDATION TESTS
    // ===================

    public function test_validation_requires_artist_and_title(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/collection/record', [
            'artist' => '',
            'title' => '',
        ]);

        $response->assertSessionHasErrors(['artist', 'title']);
    }

    public function test_validation_artist_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/collection/record', [
            'artist' => str_repeat('a', 65),
            'title' => 'Test',
        ]);

        $response->assertSessionHasErrors(['artist']);
    }

    public function test_validation_title_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/collection/record', [
            'artist' => 'Test',
            'title' => str_repeat('a', 151),
        ]);

        $response->assertSessionHasErrors(['title']);
    }

    public function test_validation_year_must_be_four_digits(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/collection/record', [
            'artist' => 'Test',
            'title' => 'Test',
            'year' => '99',
        ]);

        $response->assertSessionHasErrors(['year']);
    }
}
