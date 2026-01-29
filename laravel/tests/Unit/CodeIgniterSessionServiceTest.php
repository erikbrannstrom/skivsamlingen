<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\CodeIgniterSessionService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CodeIgniterSessionServiceTest extends TestCase
{
    use DatabaseTruncation;

    protected CodeIgniterSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CodeIgniterSessionService();

        // Clear any stale cookies
        unset($_COOKIE['ci_session']);
        unset($_COOKIE['skiv_remember']);
    }

    // ===================
    // COOKIE ENCODING/DECODING TESTS
    // ===================

    public function test_encode_cookie_creates_valid_format(): void
    {
        $data = [
            'session_id' => 'abc123',
            'last_activity' => time(),
            'user_id' => 42,
        ];

        $encoded = $this->service->encodeCookie($data);

        // Should end with 32-char MD5 hash
        $this->assertGreaterThan(32, strlen($encoded));

        // Should be decodable
        $decoded = $this->service->decodeCookie($encoded);
        $this->assertIsArray($decoded);
        $this->assertEquals('abc123', $decoded['session_id']);
        $this->assertEquals(42, $decoded['user_id']);
    }

    public function test_decode_cookie_returns_null_for_short_cookie(): void
    {
        $result = $this->service->decodeCookie('tooshort');

        $this->assertNull($result);
    }

    public function test_decode_cookie_returns_null_for_invalid_hash(): void
    {
        $data = serialize(['session_id' => 'test', 'last_activity' => time()]);
        $invalidCookie = $data . 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

        $result = $this->service->decodeCookie($invalidCookie);

        $this->assertNull($result);
    }

    public function test_decode_cookie_returns_null_for_expired_session(): void
    {
        $data = [
            'session_id' => 'expired123',
            'last_activity' => time() - 8000, // Expired (> 7200 seconds)
        ];

        $encoded = $this->service->encodeCookie($data);
        $result = $this->service->decodeCookie($encoded);

        $this->assertNull($result);
    }

    public function test_decode_cookie_accepts_valid_session(): void
    {
        $data = [
            'session_id' => 'valid123',
            'last_activity' => time() - 3600, // 1 hour ago, still valid
            'user_id' => 5,
        ];

        $encoded = $this->service->encodeCookie($data);
        $result = $this->service->decodeCookie($encoded);

        $this->assertIsArray($result);
        $this->assertEquals('valid123', $result['session_id']);
        $this->assertEquals(5, $result['user_id']);
    }

    public function test_decode_cookie_handles_url_encoded_data(): void
    {
        $data = [
            'session_id' => 'urltest123',
            'last_activity' => time(),
        ];

        $serialized = serialize($data);
        $encryptionKey = config('auth.ci_encryption_key');
        $hash = md5($serialized . $encryptionKey);

        // URL encode the serialized data
        $urlEncoded = urlencode($serialized) . $hash;

        $result = $this->service->decodeCookie($urlEncoded);

        $this->assertIsArray($result);
        $this->assertEquals('urltest123', $result['session_id']);
    }

    // ===================
    // UNSERIALIZE TESTS
    // ===================

    public function test_unserialize_handles_basic_data(): void
    {
        $data = ['foo' => 'bar', 'num' => 42];
        $serialized = serialize($data);

        $result = $this->service->unserialize($serialized);

        $this->assertEquals($data, $result);
    }

    public function test_unserialize_converts_slash_markers(): void
    {
        $data = ['path' => 'some{{slash}}path{{slash}}here'];
        $serialized = serialize($data);

        $result = $this->service->unserialize($serialized);

        $this->assertEquals('some\\path\\here', $result['path']);
    }

    public function test_unserialize_handles_slashed_data(): void
    {
        $data = ['key' => 'value'];
        $serialized = addslashes(serialize($data));

        $result = $this->service->unserialize($serialized);

        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
    }

    public function test_unserialize_returns_false_for_invalid_data(): void
    {
        $result = $this->service->unserialize('not valid serialized data');

        $this->assertFalse($result);
    }

    // ===================
    // DATABASE SESSION TESTS
    // ===================

    public function test_get_user_id_from_database_returns_user_id(): void
    {
        $sessionId = 'dbsession123';
        $userData = serialize(['user_id' => 99, 'username' => 'testuser']);

        DB::table('ci_sessions')->insert([
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'last_activity' => time(),
            'user_data' => $userData,
        ]);

        $result = $this->service->getUserIdFromDatabase($sessionId);

        $this->assertEquals(99, $result);
    }

    public function test_get_user_id_from_database_returns_null_for_missing_session(): void
    {
        $result = $this->service->getUserIdFromDatabase('nonexistent');

        $this->assertNull($result);
    }

    public function test_get_user_id_from_database_returns_null_for_expired_session(): void
    {
        $sessionId = 'expiredsession';
        $userData = serialize(['user_id' => 50]);

        DB::table('ci_sessions')->insert([
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'last_activity' => time() - 8000, // Expired
            'user_data' => $userData,
        ]);

        $result = $this->service->getUserIdFromDatabase($sessionId);

        $this->assertNull($result);
    }

    public function test_get_user_id_from_database_returns_null_for_empty_user_data(): void
    {
        $sessionId = 'emptydata';

        DB::table('ci_sessions')->insert([
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'last_activity' => time(),
            'user_data' => '',
        ]);

        $result = $this->service->getUserIdFromDatabase($sessionId);

        $this->assertNull($result);
    }

    public function test_get_user_id_from_database_returns_null_for_no_user_id(): void
    {
        $sessionId = 'nouserid';
        $userData = serialize(['some_other_key' => 'value']);

        DB::table('ci_sessions')->insert([
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'last_activity' => time(),
            'user_data' => $userData,
        ]);

        $result = $this->service->getUserIdFromDatabase($sessionId);

        $this->assertNull($result);
    }

    // ===================
    // GET USER ID TESTS
    // ===================

    public function test_get_user_id_returns_null_when_no_cookie(): void
    {
        unset($_COOKIE['ci_session']);

        $result = $this->service->getUserId();

        $this->assertNull($result);
    }

    public function test_get_user_id_returns_false_for_invalid_cookie(): void
    {
        $_COOKIE['ci_session'] = 'invalid' . str_repeat('x', 32);

        $result = $this->service->getUserId();

        $this->assertFalse($result);
    }

    public function test_get_user_id_returns_user_id_from_cookie(): void
    {
        $data = [
            'session_id' => 'cookietest',
            'last_activity' => time(),
            'user_id' => 123,
        ];

        $_COOKIE['ci_session'] = $this->service->encodeCookie($data);

        $result = $this->service->getUserId();

        $this->assertEquals(123, $result);
    }

    public function test_get_user_id_looks_up_database_session(): void
    {
        // Create database session
        $sessionId = 'dbtest456';
        $userData = serialize(['user_id' => 77, 'username' => 'dbuser']);

        DB::table('ci_sessions')->insert([
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'last_activity' => time(),
            'user_data' => $userData,
        ]);

        // Create cookie pointing to database session (no user_id in cookie)
        $cookieData = [
            'session_id' => $sessionId,
            'last_activity' => time(),
        ];

        $_COOKIE['ci_session'] = $this->service->encodeCookie($cookieData);

        $result = $this->service->getUserId();

        $this->assertEquals(77, $result);
    }

    // ===================
    // LOGIN/LOGOUT TESTS
    // ===================

    public function test_login_user_creates_session_when_none_exists(): void
    {
        unset($_COOKIE['ci_session']);

        $user = User::factory()->create(['username' => 'newlogin']);

        $this->service->loginUser($user);

        // Should have created a cookie
        $this->assertArrayHasKey('ci_session', $_COOKIE);

        // Should have created database session
        $this->assertDatabaseHas('ci_sessions', [
            'user_data' => serialize([
                'user_id' => $user->id,
                'username' => 'newlogin',
            ]),
        ]);
    }

    public function test_login_user_updates_existing_session(): void
    {
        // Create existing session
        $sessionId = 'existingsession';
        DB::table('ci_sessions')->insert([
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'last_activity' => time() - 100,
            'user_data' => serialize([]),
        ]);

        $cookieData = [
            'session_id' => $sessionId,
            'last_activity' => time() - 100,
        ];
        $_COOKIE['ci_session'] = $this->service->encodeCookie($cookieData);

        $user = User::factory()->create(['username' => 'updatelogin']);

        $this->service->loginUser($user);

        // Should have updated database session
        $session = DB::table('ci_sessions')
            ->where('session_id', $sessionId)
            ->first();

        $userData = unserialize($session->user_data);
        $this->assertEquals($user->id, $userData['user_id']);
        $this->assertEquals('updatelogin', $userData['username']);
    }

    public function test_logout_user_clears_user_data(): void
    {
        // Create session with user
        $sessionId = 'logoutsession';
        $user = User::factory()->create(['username' => 'logoutuser']);

        DB::table('ci_sessions')->insert([
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'last_activity' => time(),
            'user_data' => serialize([
                'user_id' => $user->id,
                'username' => 'logoutuser',
            ]),
        ]);

        $cookieData = [
            'session_id' => $sessionId,
            'last_activity' => time(),
            'user_id' => $user->id,
            'username' => 'logoutuser',
        ];
        $_COOKIE['ci_session'] = $this->service->encodeCookie($cookieData);

        $this->service->logoutUser();

        // Database session should have no user
        $session = DB::table('ci_sessions')
            ->where('session_id', $sessionId)
            ->first();

        $userData = unserialize($session->user_data);
        $this->assertArrayNotHasKey('user_id', $userData);
        $this->assertArrayNotHasKey('username', $userData);

        // Cookie should still exist but without user data
        $newCookieData = $this->service->decodeCookie($_COOKIE['ci_session']);
        $this->assertArrayNotHasKey('user_id', $newCookieData);
    }

    // ===================
    // PERSISTENT LOGIN TESTS
    // ===================

    public function test_get_persistent_login_returns_null_when_no_cookie(): void
    {
        unset($_COOKIE['skiv_remember']);

        $result = $this->service->getPersistentLogin();

        $this->assertNull($result);
    }

    public function test_get_persistent_login_returns_null_for_invalid_format(): void
    {
        $_COOKIE['skiv_remember'] = 'invalid-format';

        $result = $this->service->getPersistentLogin();

        $this->assertNull($result);
    }

    public function test_get_persistent_login_returns_null_for_missing_record(): void
    {
        $_COOKIE['skiv_remember'] = '1;someseries;sometoken';

        $result = $this->service->getPersistentLogin();

        $this->assertNull($result);
    }

    public function test_get_persistent_login_returns_null_for_wrong_token(): void
    {
        DB::table('persistent_logins')->insert([
            'user_id' => 5,
            'series' => 'testseries',
            'token' => 12345,
        ]);

        $_COOKIE['skiv_remember'] = '5;testseries;99999'; // Wrong token

        $result = $this->service->getPersistentLogin();

        $this->assertNull($result);
    }

    public function test_get_persistent_login_returns_data_for_valid_cookie(): void
    {
        DB::table('persistent_logins')->insert([
            'user_id' => 8,
            'series' => 'validseries',
            'token' => 54321,
        ]);

        $_COOKIE['skiv_remember'] = '8;validseries;54321';

        $result = $this->service->getPersistentLogin();

        $this->assertIsArray($result);
        $this->assertEquals(8, $result[0]);
        $this->assertEquals('validseries', $result[1]);
        $this->assertEquals('54321', $result[2]);
    }

    public function test_clear_persistent_login_removes_cookie_and_record(): void
    {
        DB::table('persistent_logins')->insert([
            'user_id' => 10,
            'series' => 'clearseries',
            'token' => 11111,
        ]);

        $_COOKIE['skiv_remember'] = '10;clearseries;11111';

        $this->service->clearPersistentLogin();

        // Database record should be deleted
        $this->assertDatabaseMissing('persistent_logins', [
            'user_id' => 10,
            'series' => 'clearseries',
        ]);

        // Cookie should be cleared
        $this->assertArrayNotHasKey('skiv_remember', $_COOKIE);
    }

    public function test_create_persistent_login_inserts_record_and_sets_cookie(): void
    {
        $userId = 42;

        $this->service->createPersistentLogin($userId);

        // Should have created database record
        $this->assertDatabaseHas('persistent_logins', [
            'user_id' => $userId,
        ]);

        // Should have set cookie
        $this->assertArrayHasKey('skiv_remember', $_COOKIE);

        // Cookie should contain user_id
        $this->assertStringStartsWith('42;', $_COOKIE['skiv_remember']);

        // Cookie format should be user_id;series;token
        $parts = explode(';', $_COOKIE['skiv_remember']);
        $this->assertCount(3, $parts);
        $this->assertEquals('42', $parts[0]);
        $this->assertEquals(40, strlen($parts[1])); // SHA1 hash length
    }

    // ===================
    // COOKIE HELPER TESTS
    // ===================

    public function test_get_raw_cookie_returns_cookie_value(): void
    {
        $_COOKIE['test_cookie'] = 'test_value';

        $result = $this->service->getRawCookie('test_cookie');

        $this->assertEquals('test_value', $result);
    }

    public function test_get_raw_cookie_returns_null_for_missing_cookie(): void
    {
        unset($_COOKIE['missing_cookie']);

        $result = $this->service->getRawCookie('missing_cookie');

        $this->assertNull($result);
    }

    public function test_set_cookie_updates_superglobal(): void
    {
        $this->service->setCookie('new_cookie', 'new_value', 3600);

        $this->assertEquals('new_value', $_COOKIE['new_cookie']);
    }

    public function test_clear_cookie_removes_from_superglobal(): void
    {
        $_COOKIE['to_clear'] = 'some_value';

        $this->service->clearCookie('to_clear');

        $this->assertArrayNotHasKey('to_clear', $_COOKIE);
    }
}
