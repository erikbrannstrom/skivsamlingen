<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for reading and writing CodeIgniter session data.
 *
 * Provides a unified interface for working with CodeIgniter's session system,
 * including cookie-based and database-backed sessions.
 */
class CodeIgniterSessionService
{
    /**
     * Session expiration time in seconds (2 hours).
     */
    public const SESSION_EXPIRATION = 7200;

    /**
     * Get the user ID from the current CodeIgniter session.
     *
     * @return int|null|false int if logged in, null if no session, false if session exists but logged out
     */
    public function getUserId(): int|null|false
    {
        $cookie = $this->getRawCookie('ci_session');

        if (!$cookie) {
            return null;
        }

        $sessionData = $this->decodeCookie($cookie);

        if (!$sessionData) {
            return false;
        }

        // User ID directly in cookie (cookie-only sessions)
        if (isset($sessionData['user_id'])) {
            return (int) $sessionData['user_id'];
        }

        // Database sessions - look up user_data
        if (isset($sessionData['session_id'])) {
            return $this->getUserIdFromDatabase($sessionData['session_id']) ?? false;
        }

        return false;
    }

    /**
     * Get user ID from database session.
     */
    public function getUserIdFromDatabase(string $sessionId): ?int
    {
        $session = $this->getDatabaseSession($sessionId);

        if (!$session) {
            return null;
        }

        if (empty($session->user_data)) {
            return null;
        }

        $userData = $this->unserialize($session->user_data);

        if (!is_array($userData) || !isset($userData['user_id'])) {
            return null;
        }

        return (int) $userData['user_id'];
    }

    /**
     * Get a database session by ID, checking expiration.
     */
    public function getDatabaseSession(string $sessionId): ?object
    {
        $session = DB::table('ci_sessions')
            ->where('session_id', $sessionId)
            ->first();

        if (!$session) {
            return null;
        }

        // Check expiration
        if (($session->last_activity + self::SESSION_EXPIRATION) < time()) {
            return null;
        }

        return $session;
    }

    /**
     * Decode a CodeIgniter session cookie.
     *
     * Format: serialized_data + md5(serialized_data + encryption_key)
     *
     * @return array|null The decoded session data, or null if invalid
     */
    public function decodeCookie(string $cookie): ?array
    {
        if (strlen($cookie) <= 32) {
            return null;
        }

        $hash = substr($cookie, -32);
        $serializedData = substr($cookie, 0, -32);
        $encryptionKey = config('auth.ci_encryption_key');

        // Verify hash
        if ($hash !== md5($serializedData . $encryptionKey)) {
            // Try URL-decoded version (cookies may be encoded in transit)
            $decodedData = urldecode($serializedData);
            if ($hash !== md5($decodedData . $encryptionKey)) {
                return null;
            }
            $serializedData = $decodedData;
        }

        $data = $this->unserialize($serializedData);

        if (!is_array($data)) {
            return null;
        }

        // Validate required fields and expiration for cookie-based sessions
        if (isset($data['session_id'], $data['last_activity'])) {
            if (($data['last_activity'] + self::SESSION_EXPIRATION) < time()) {
                return null;
            }
        }

        return $data;
    }

    /**
     * Encode data into a CodeIgniter session cookie format.
     */
    public function encodeCookie(array $data): string
    {
        $serialized = serialize($data);
        $encryptionKey = config('auth.ci_encryption_key');
        return $serialized . md5($serialized . $encryptionKey);
    }

    /**
     * Unserialize CodeIgniter session data.
     */
    public function unserialize(string $data): mixed
    {
        $unserialized = @unserialize(stripslashes($data));

        if (is_array($unserialized)) {
            foreach ($unserialized as $key => $val) {
                if (is_string($val)) {
                    $unserialized[$key] = str_replace('{{slash}}', '\\', $val);
                }
            }
        }

        return $unserialized;
    }

    /**
     * Update the CI session to log in a user.
     */
    public function loginUser(User $user): void
    {
        $cookie = $this->getRawCookie('ci_session');
        $cookieData = $cookie ? $this->decodeCookie($cookie) : null;

        if ($cookieData && isset($cookieData['session_id'])) {
            $this->updateExistingSession($cookieData, $user);
        } else {
            $this->createNewSession($user);
        }
    }

    /**
     * Update an existing CI session with user data.
     */
    protected function updateExistingSession(array $cookieData, User $user): void
    {
        $sessionId = $cookieData['session_id'];
        $session = DB::table('ci_sessions')
            ->where('session_id', $sessionId)
            ->first();

        if (!$session) {
            $this->createNewSession($user);
            return;
        }

        // Update database session
        $userData = $session->user_data ? $this->unserialize($session->user_data) : [];
        if (!is_array($userData)) {
            $userData = [];
        }

        $userData['user_id'] = $user->id;
        $userData['username'] = $user->username;

        DB::table('ci_sessions')
            ->where('session_id', $sessionId)
            ->update([
                'user_data' => serialize($userData),
                'last_activity' => time(),
            ]);

        // Update cookie
        $cookieData['user_id'] = $user->id;
        $cookieData['username'] = $user->username;
        $cookieData['last_activity'] = time();
        $this->setCookie('ci_session', $this->encodeCookie($cookieData), self::SESSION_EXPIRATION);
    }

    /**
     * Create a new CI session for a user.
     */
    protected function createNewSession(User $user): void
    {
        $sessionId = md5(uniqid(mt_rand(), true));
        $ipAddress = request()->ip() ?? '0.0.0.0';
        $userAgent = substr(request()->userAgent() ?? '', 0, 120);

        $cookieData = [
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'last_activity' => time(),
            'user_id' => $user->id,
            'username' => $user->username,
        ];

        $dbUserData = serialize([
            'user_id' => $user->id,
            'username' => $user->username,
        ]);

        DB::table('ci_sessions')->insert([
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'last_activity' => time(),
            'user_data' => $dbUserData,
        ]);

        $this->setCookie('ci_session', $this->encodeCookie($cookieData), self::SESSION_EXPIRATION);
    }

    /**
     * Clear user data from the current CI session (logout).
     */
    public function logoutUser(): void
    {
        $cookie = $this->getRawCookie('ci_session');

        if (!$cookie) {
            return;
        }

        $cookieData = $this->decodeCookie($cookie);

        if (!$cookieData || !isset($cookieData['session_id'])) {
            return;
        }

        $sessionId = $cookieData['session_id'];
        $session = DB::table('ci_sessions')
            ->where('session_id', $sessionId)
            ->first();

        if ($session) {
            // Remove user data from database session
            $userData = $session->user_data ? $this->unserialize($session->user_data) : [];
            if (is_array($userData)) {
                unset($userData['user_id'], $userData['username']);
            } else {
                $userData = [];
            }

            DB::table('ci_sessions')
                ->where('session_id', $sessionId)
                ->update([
                    'user_data' => serialize($userData),
                    'last_activity' => time(),
                ]);
        }

        // Remove user data from cookie
        unset($cookieData['user_id'], $cookieData['username']);
        $cookieData['last_activity'] = time();
        $this->setCookie('ci_session', $this->encodeCookie($cookieData), self::SESSION_EXPIRATION);
    }

    /**
     * Create a persistent login (remember me) for a user.
     */
    public function createPersistentLogin(int $userId): void
    {
        $token = mt_rand() + mt_rand();
        $series = sha1(mt_rand());

        DB::table('persistent_logins')->insert([
            'user_id' => $userId,
            'series' => $series,
            'token' => $token,
        ]);

        $cookieValue = "{$userId};{$series};{$token}";
        $this->setCookie('skiv_remember', $cookieValue, 60 * 60 * 24 * 30); // 30 days
    }

    /**
     * Check persistent login and return detailed result.
     *
     * @return array{valid: bool, user_id: int|null, series: string|null, token: string|null, theft_suspected: bool}
     */
    public function checkPersistentLogin(): array
    {
        $result = [
            'valid' => false,
            'user_id' => null,
            'series' => null,
            'token' => null,
            'theft_suspected' => false,
        ];

        $cookie = $this->getRawCookie('skiv_remember');

        if (!$cookie) {
            return $result;
        }

        $parts = explode(';', $cookie);

        if (count($parts) !== 3) {
            return $result;
        }

        [$userId, $series, $token] = $parts;
        $result['user_id'] = (int) $userId;
        $result['series'] = $series;
        $result['token'] = $token;

        $record = DB::table('persistent_logins')
            ->where('user_id', $userId)
            ->where('series', $series)
            ->first();

        if (!$record) {
            // Series not found - cookie is simply invalid
            return $result;
        }

        if ($record->token != $token) {
            // Token mismatch with valid series - potential cookie theft!
            $result['theft_suspected'] = true;
            Log::warning('Persistent login token mismatch - potential cookie theft detected', [
                'user_id' => $userId,
                'series' => $series,
            ]);
            return $result;
        }

        // Valid persistent login
        $result['valid'] = true;
        return $result;
    }

    /**
     * Get persistent login data if valid.
     *
     * @return array|null [user_id, series, token] or null if invalid
     * @deprecated Use checkPersistentLogin() for better security handling
     */
    public function getPersistentLogin(): ?array
    {
        $result = $this->checkPersistentLogin();

        if (!$result['valid']) {
            return null;
        }

        return [$result['user_id'], $result['series'], $result['token']];
    }

    /**
     * Rotate the token for a persistent login (security measure on successful auth).
     *
     * Generates a new token, updates the database, and updates the cookie.
     */
    public function rotatePersistentLoginToken(int $userId, string $series): void
    {
        $newToken = mt_rand() + mt_rand();

        DB::table('persistent_logins')
            ->where('user_id', $userId)
            ->where('series', $series)
            ->update(['token' => $newToken]);

        $cookieValue = "{$userId};{$series};{$newToken}";
        $this->setCookie('skiv_remember', $cookieValue, 60 * 60 * 24 * 30); // 30 days
    }

    /**
     * Invalidate all sessions for a user (security measure after suspected theft).
     *
     * Deletes all persistent logins and CI sessions for the specified user.
     */
    public function invalidateAllUserSessions(int $userId): void
    {
        // Delete all persistent logins for this user
        DB::table('persistent_logins')
            ->where('user_id', $userId)
            ->delete();

        // Delete all CI sessions for this user
        // CI sessions store user_id in serialized user_data, so we need to find them
        $sessions = DB::table('ci_sessions')->get();

        foreach ($sessions as $session) {
            if (empty($session->user_data)) {
                continue;
            }

            $userData = $this->unserialize($session->user_data);

            if (is_array($userData) && isset($userData['user_id']) && (int) $userData['user_id'] === $userId) {
                DB::table('ci_sessions')
                    ->where('session_id', $session->session_id)
                    ->delete();
            }
        }

        // Clear the remember cookie
        $this->clearCookie('skiv_remember');
    }

    /**
     * Clear persistent login cookie and database record.
     */
    public function clearPersistentLogin(): void
    {
        $cookie = $this->getRawCookie('skiv_remember');

        if (!$cookie) {
            return;
        }

        $parts = explode(';', $cookie);

        if (count($parts) === 3) {
            [$userId, $series] = $parts;

            DB::table('persistent_logins')
                ->where('user_id', $userId)
                ->where('series', $series)
                ->delete();
        }

        $this->clearCookie('skiv_remember');
    }

    /**
     * Get a raw cookie value, preferring $_COOKIE over request->cookie.
     */
    public function getRawCookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Set a cookie.
     */
    public function setCookie(string $name, string $value, int $ttl): void
    {
        setcookie($name, $value, [
            'expires' => time() + $ttl,
            'path' => '/',
            'domain' => '',
            'secure' => request()->isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $_COOKIE[$name] = $value;
    }

    /**
     * Clear a cookie.
     */
    public function clearCookie(string $name): void
    {
        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => request()->isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        unset($_COOKIE[$name]);
    }
}
