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
}
