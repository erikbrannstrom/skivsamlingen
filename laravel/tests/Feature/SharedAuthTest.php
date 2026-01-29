<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SharedAuthTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any stale auth state between tests
        Auth::logout();
        unset($_COOKIE['ci_session']);
        unset($_COOKIE['skiv_remember']);
    }

    /**
     * Create a valid CodeIgniter session cookie value.
     */
    protected function createCiSessionCookie(array $sessionData): string
    {
        $serialized = serialize($sessionData);
        $encryptionKey = config('auth.ci_encryption_key');
        $hash = md5($serialized . $encryptionKey);

        return $serialized . $hash;
    }

    /**
     * Create a CI database session entry.
     */
    protected function createCiDatabaseSession(string $sessionId, ?int $userId = null, ?int $lastActivity = null): void
    {
        $userData = [];
        if ($userId !== null) {
            $userData['user_id'] = $userId;
            $userData['username'] = 'testuser';
        }

        DB::table('ci_sessions')->insert([
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'last_activity' => $lastActivity ?? time(),
            'user_data' => serialize($userData),
        ]);
    }

    /**
     * Test that requests without CI session don't interfere with Laravel auth.
     */
    public function test_no_ci_session_allows_laravel_auth(): void
    {
        $user = User::factory()->create();

        // Using actingAs without any CI session should work
        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    /**
     * Test that valid CI database session logs user into Laravel.
     */
    public function test_valid_ci_database_session_authenticates_user(): void
    {
        $user = User::factory()->create();
        $sessionId = 'test_session_' . uniqid();

        // Create CI database session with user logged in
        $this->createCiDatabaseSession($sessionId, $user->id);

        // Create cookie pointing to database session
        $cookie = $this->createCiSessionCookie([
            'session_id' => $sessionId,
            'last_activity' => time(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        // Make request with CI session cookie
        $_COOKIE['ci_session'] = $cookie;

        $response = $this->get('/');

        $response->assertStatus(200);
        // User should be logged in via SharedAuth
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());

        unset($_COOKIE['ci_session']);
    }

    /**
     * Test that CI session without user_id does not authenticate.
     */
    public function test_ci_session_without_user_does_not_authenticate(): void
    {
        $sessionId = 'test_session_' . uniqid();

        // Create CI database session without user (logged out)
        $this->createCiDatabaseSession($sessionId, null);

        $cookie = $this->createCiSessionCookie([
            'session_id' => $sessionId,
            'last_activity' => time(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $_COOKIE['ci_session'] = $cookie;

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse(Auth::check());

        unset($_COOKIE['ci_session']);
    }

    /**
     * Test that Laravel logs out when CI session shows user logged out.
     *
     * This is the key test for the logout synchronization fix.
     */
    public function test_laravel_logs_out_when_ci_session_logged_out(): void
    {
        $user = User::factory()->create();
        $sessionId = 'test_session_' . uniqid();

        // First, create a CI session with the user logged in
        $this->createCiDatabaseSession($sessionId, $user->id);

        $cookie = $this->createCiSessionCookie([
            'session_id' => $sessionId,
            'last_activity' => time(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $_COOKIE['ci_session'] = $cookie;

        // First request - user gets logged in
        $this->get('/');
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());

        // Now simulate CI logout by removing user_id from database session
        DB::table('ci_sessions')
            ->where('session_id', $sessionId)
            ->update(['user_data' => serialize([])]);

        // Second request - user should be logged out
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse(Auth::check());

        unset($_COOKIE['ci_session']);
    }

    /**
     * Test that Laravel switches users when CI session has different user.
     */
    public function test_laravel_switches_user_when_ci_session_changes(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $sessionId = 'test_session_' . uniqid();

        // Create CI session with user1
        $this->createCiDatabaseSession($sessionId, $user1->id);

        $cookie = $this->createCiSessionCookie([
            'session_id' => $sessionId,
            'last_activity' => time(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $_COOKIE['ci_session'] = $cookie;

        // First request - user1 gets logged in
        $this->get('/');
        $this->assertEquals($user1->id, Auth::id());

        // Switch CI session to user2
        DB::table('ci_sessions')
            ->where('session_id', $sessionId)
            ->update(['user_data' => serialize(['user_id' => $user2->id, 'username' => 'user2'])]);

        // Second request - should now be user2
        $this->get('/');

        $this->assertTrue(Auth::check());
        $this->assertEquals($user2->id, Auth::id());

        unset($_COOKIE['ci_session']);
    }

    /**
     * Test that expired CI session does not authenticate.
     */
    public function test_expired_ci_session_does_not_authenticate(): void
    {
        $user = User::factory()->create();
        $sessionId = 'test_session_' . uniqid();

        // Create expired CI database session (3 hours old)
        $this->createCiDatabaseSession($sessionId, $user->id, time() - 10800);

        $cookie = $this->createCiSessionCookie([
            'session_id' => $sessionId,
            'last_activity' => time() - 10800, // 3 hours ago (expired)
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $_COOKIE['ci_session'] = $cookie;

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse(Auth::check());

        unset($_COOKIE['ci_session']);
    }

    /**
     * Test that invalid CI session hash does not authenticate.
     */
    public function test_invalid_ci_session_hash_does_not_authenticate(): void
    {
        $user = User::factory()->create();

        // Create cookie with invalid hash
        $sessionData = serialize([
            'session_id' => 'test_session',
            'last_activity' => time(),
            'user_id' => $user->id,
        ]);
        $invalidCookie = $sessionData . 'invalidhash12345678901234567890';

        $_COOKIE['ci_session'] = $invalidCookie;

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse(Auth::check());

        unset($_COOKIE['ci_session']);
    }

    /**
     * Test persistent login cookie authenticates user.
     */
    public function test_persistent_login_cookie_authenticates_user(): void
    {
        $user = User::factory()->create();
        $series = sha1(uniqid());
        $token = '12345';

        // Create persistent login record
        DB::table('persistent_logins')->insert([
            'user_id' => $user->id,
            'series' => $series,
            'token' => $token,
        ]);

        // Set the remember cookie via $_COOKIE
        $_COOKIE['skiv_remember'] = "{$user->id};{$series};{$token}";

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());

        unset($_COOKIE['skiv_remember']);
    }

    /**
     * Test persistent login with wrong token does not authenticate.
     */
    public function test_persistent_login_wrong_token_does_not_authenticate(): void
    {
        $user = User::factory()->create();
        $series = sha1(uniqid());

        // Create persistent login record with different token
        DB::table('persistent_logins')->insert([
            'user_id' => $user->id,
            'series' => $series,
            'token' => 'correct_token',
        ]);

        $_COOKIE['skiv_remember'] = "{$user->id};{$series};wrong_token";

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse(Auth::check());

        unset($_COOKIE['skiv_remember']);
    }

    /**
     * Test that token mismatch invalidates ALL sessions for the user.
     *
     * This is a security measure against cookie theft - if an attacker uses a stolen
     * cookie after the legitimate user has already used it (rotating the token),
     * the mismatch indicates compromise and all sessions should be invalidated.
     *
     * This includes:
     * - All persistent logins (remember me) for the user
     * - All CI sessions for the user
     * - A warning message shown to the user about the potential attack
     */
    public function test_persistent_login_token_mismatch_invalidates_all_user_sessions(): void
    {
        $user = User::factory()->create(['username' => 'compromised_user']);
        $series1 = sha1(uniqid() . '1');
        $series2 = sha1(uniqid() . '2');
        $series3 = sha1(uniqid() . '3');

        // Create multiple persistent login records for this user (e.g., different devices)
        DB::table('persistent_logins')->insert([
            ['user_id' => $user->id, 'series' => $series1, 'token' => 'token1'],
            ['user_id' => $user->id, 'series' => $series2, 'token' => 'token2'],
            ['user_id' => $user->id, 'series' => $series3, 'token' => 'token3'],
        ]);

        // Create CI sessions for this user (active sessions on different devices)
        $userSessionData = serialize(['user_id' => $user->id, 'username' => $user->username]);
        DB::table('ci_sessions')->insert([
            [
                'session_id' => 'user_session_1',
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Device 1',
                'last_activity' => time(),
                'user_data' => $userSessionData,
            ],
            [
                'session_id' => 'user_session_2',
                'ip_address' => '192.168.1.2',
                'user_agent' => 'Device 2',
                'last_activity' => time(),
                'user_data' => $userSessionData,
            ],
        ]);

        // Also create a persistent login and CI session for another user (should not be affected)
        $otherUser = User::factory()->create(['username' => 'other_user']);
        $otherSeries = sha1(uniqid() . 'other');
        DB::table('persistent_logins')->insert([
            'user_id' => $otherUser->id,
            'series' => $otherSeries,
            'token' => 'other_token',
        ]);
        DB::table('ci_sessions')->insert([
            'session_id' => 'other_user_session',
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Other Device',
            'last_activity' => time(),
            'user_data' => serialize(['user_id' => $otherUser->id, 'username' => $otherUser->username]),
        ]);

        // Attempt authentication with correct series but wrong token (simulates stolen cookie)
        $_COOKIE['skiv_remember'] = "{$user->id};{$series1};wrong_token";

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse(Auth::check());

        // ALL persistent logins for the compromised user should be deleted
        $this->assertDatabaseMissing('persistent_logins', ['user_id' => $user->id, 'series' => $series1]);
        $this->assertDatabaseMissing('persistent_logins', ['user_id' => $user->id, 'series' => $series2]);
        $this->assertDatabaseMissing('persistent_logins', ['user_id' => $user->id, 'series' => $series3]);

        // ALL CI sessions for the compromised user should be deleted
        $this->assertDatabaseMissing('ci_sessions', ['session_id' => 'user_session_1']);
        $this->assertDatabaseMissing('ci_sessions', ['session_id' => 'user_session_2']);

        // Other user's persistent login and CI session should NOT be affected
        $this->assertDatabaseHas('persistent_logins', ['user_id' => $otherUser->id, 'series' => $otherSeries]);
        $this->assertDatabaseHas('ci_sessions', ['session_id' => 'other_user_session']);

        // User should see a warning about the potential security breach
        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'säkerhet',
            strtolower(session('error')),
            'Warning message should mention security concern'
        );

        unset($_COOKIE['skiv_remember']);
    }

    /**
     * Test that successful persistent login rotates the token.
     *
     * After successful authentication via persistent login, the token should be
     * regenerated and updated both in the database and in the cookie. This ensures
     * that each token can only be used once, limiting the window for replay attacks.
     */
    public function test_persistent_login_success_rotates_token(): void
    {
        $user = User::factory()->create();
        $series = sha1(uniqid());
        $originalToken = '12345';

        // Create persistent login record
        DB::table('persistent_logins')->insert([
            'user_id' => $user->id,
            'series' => $series,
            'token' => $originalToken,
        ]);

        $_COOKIE['skiv_remember'] = "{$user->id};{$series};{$originalToken}";

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());

        // The series should still exist but with a NEW token
        $record = DB::table('persistent_logins')
            ->where('user_id', $user->id)
            ->where('series', $series)
            ->first();

        $this->assertNotNull($record, 'Persistent login record should still exist');
        $this->assertNotEquals($originalToken, $record->token, 'Token should have been rotated');

        // The cookie should also be updated with the new token
        $this->assertArrayHasKey('skiv_remember', $_COOKIE);
        $cookieParts = explode(';', $_COOKIE['skiv_remember']);
        $this->assertCount(3, $cookieParts);
        $this->assertEquals($user->id, $cookieParts[0]);
        $this->assertEquals($series, $cookieParts[1]);
        $this->assertEquals($record->token, $cookieParts[2], 'Cookie token should match new database token');
        $this->assertNotEquals($originalToken, $cookieParts[2], 'Cookie token should not be the original');

        unset($_COOKIE['skiv_remember']);
    }
}
