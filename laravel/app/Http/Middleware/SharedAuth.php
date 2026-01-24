<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to share authentication with CodeIgniter.
 *
 * This middleware reads CodeIgniter's session cookie and persistent login
 * cookie to authenticate users in Laravel, enabling both applications to
 * share the same authentication state during the migration period.
 *
 * Supports both cookie-based sessions and database sessions.
 */
class SharedAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::debug('SharedAuth: Starting', [
            'already_authenticated' => Auth::check(),
            'cookies_available' => array_keys($_COOKIE),
        ]);

        // Skip if user is already authenticated in Laravel
        if (Auth::check()) {
            Log::debug('SharedAuth: Already authenticated');
            return $next($request);
        }

        // Try to authenticate from CodeIgniter session cookie
        if ($this->authenticateFromSession($request)) {
            Log::debug('SharedAuth: Authenticated via CI session');
            return $next($request);
        }

        // Try to authenticate from persistent login cookie
        if ($this->authenticateFromPersistentLogin($request)) {
            Log::debug('SharedAuth: Authenticated via persistent login');
            return $next($request);
        }

        Log::debug('SharedAuth: No authentication found');
        return $next($request);
    }

    /**
     * Attempt to authenticate from CodeIgniter's session cookie.
     */
    protected function authenticateFromSession(Request $request): bool
    {
        // Check both request->cookie and $_COOKIE
        $sessionCookie = $request->cookie('ci_session');
        $rawCookie = $_COOKIE['ci_session'] ?? null;

        Log::debug('SharedAuth: Session cookie check', [
            'request_cookie_exists' => !empty($sessionCookie),
            'request_cookie_length' => strlen($sessionCookie ?? ''),
            'raw_cookie_exists' => !empty($rawCookie),
            'raw_cookie_length' => strlen($rawCookie ?? ''),
        ]);

        // Prefer raw cookie if request cookie is empty (Laravel encryption issue)
        if (!$sessionCookie && $rawCookie) {
            Log::debug('SharedAuth: Using raw $_COOKIE instead of request->cookie');
            $sessionCookie = $rawCookie;
        }

        if (!$sessionCookie) {
            Log::debug('SharedAuth: No ci_session cookie found');
            return false;
        }

        try {
            $sessionData = $this->decodeCodeIgniterSession($sessionCookie);

            if (!$sessionData) {
                Log::debug('SharedAuth: Failed to decode session');
                return false;
            }

            Log::debug('SharedAuth: Session decoded', [
                'keys' => array_keys($sessionData),
                'has_user_id' => isset($sessionData['user_id']),
                'has_session_id' => isset($sessionData['session_id']),
            ]);

            // If user_id is directly in the cookie (cookie-only sessions)
            if (isset($sessionData['user_id'])) {
                return $this->loginUser($sessionData['user_id']);
            }

            // If using database sessions, look up user_data from the database
            if (isset($sessionData['session_id'])) {
                return $this->authenticateFromDatabaseSession($sessionData['session_id']);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to decode CodeIgniter session', [
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Authenticate from database session by looking up the session_id.
     */
    protected function authenticateFromDatabaseSession(string $sessionId): bool
    {
        Log::debug('SharedAuth: Looking up database session', ['session_id' => $sessionId]);

        $session = DB::table('ci_sessions')
            ->where('session_id', $sessionId)
            ->first();

        if (!$session) {
            Log::debug('SharedAuth: Session not found in database');
            return false;
        }

        // Check session expiration
        $sessionExpiration = 7200; // 2 hours
        if (($session->last_activity + $sessionExpiration) < time()) {
            Log::debug('SharedAuth: Database session expired', [
                'last_activity' => $session->last_activity,
                'now' => time(),
            ]);
            return false;
        }

        // Unserialize the user_data
        if (empty($session->user_data)) {
            Log::debug('SharedAuth: No user_data in database session');
            return false;
        }

        $userData = $this->unserializeSessionData($session->user_data);

        if (!$userData || !is_array($userData)) {
            Log::debug('SharedAuth: Failed to unserialize user_data');
            return false;
        }

        Log::debug('SharedAuth: Database session user_data', [
            'keys' => array_keys($userData),
            'has_user_id' => isset($userData['user_id']),
        ]);

        if (isset($userData['user_id'])) {
            return $this->loginUser($userData['user_id']);
        }

        Log::debug('SharedAuth: No user_id in database session user_data');
        return false;
    }

    /**
     * Login a user by their ID.
     */
    protected function loginUser(int $userId): bool
    {
        $user = User::find($userId);

        if ($user) {
            Log::debug('SharedAuth: User found, logging in', ['username' => $user->username]);
            Auth::login($user);
            return true;
        }

        Log::debug('SharedAuth: User not found in DB', ['user_id' => $userId]);
        return false;
    }

    /**
     * Decode CodeIgniter's session cookie format.
     *
     * Format (when sess_encrypt_cookie is FALSE):
     * - serialized_data + md5(serialized_data + encryption_key)
     * - Last 32 characters are the MD5 hash
     *
     * @param string $cookie The raw cookie value
     * @return array|null The session data array, or null if invalid
     */
    protected function decodeCodeIgniterSession(string $cookie): ?array
    {
        Log::debug('SharedAuth: Decoding CI session', ['cookie_length' => strlen($cookie)]);

        // Cookie must have at least 32 chars (for the hash) + some data
        if (strlen($cookie) <= 32) {
            Log::debug('SharedAuth: Cookie too short');
            return null;
        }

        // Extract the hash (last 32 characters) and serialized data
        $hash = substr($cookie, -32);
        $serializedData = substr($cookie, 0, -32);

        // Verify the hash
        $encryptionKey = config('auth.ci_encryption_key');
        $hashMatches = ($hash === md5($serializedData . $encryptionKey));

        Log::debug('SharedAuth: Hash check (raw)', [
            'hash' => $hash,
            'expected' => md5($serializedData . $encryptionKey),
            'match' => $hashMatches,
            'key_length' => strlen($encryptionKey),
        ]);

        // If hash doesn't match, try with URL-decoded version
        // (cookies may be URL-encoded in transit)
        if (!$hashMatches) {
            $decodedData = urldecode($serializedData);
            $decodedHashMatches = ($hash === md5($decodedData . $encryptionKey));

            Log::debug('SharedAuth: Hash check (url-decoded)', [
                'expected' => md5($decodedData . $encryptionKey),
                'match' => $decodedHashMatches,
            ]);

            if ($decodedHashMatches) {
                $hashMatches = true;
                $serializedData = $decodedData;
            }
        }

        if (!$hashMatches) {
            Log::debug('SharedAuth: Hash mismatch - rejecting');
            return null;
        }

        // Unserialize the data
        $data = $this->unserializeSessionData($serializedData);

        if (!is_array($data)) {
            Log::debug('SharedAuth: Unserialize failed');
            return null;
        }

        // Validate required session fields
        if (!isset($data['session_id'], $data['last_activity'])) {
            Log::debug('SharedAuth: Missing required fields');
            return null;
        }

        // Check session expiration (default 2 hours = 7200 seconds)
        $sessionExpiration = 7200;
        if (($data['last_activity'] + $sessionExpiration) < time()) {
            Log::debug('SharedAuth: Session expired', [
                'last_activity' => $data['last_activity'],
                'now' => time(),
                'age' => time() - $data['last_activity'],
            ]);
            return null;
        }

        Log::debug('SharedAuth: Session valid');
        return $data;
    }

    /**
     * Unserialize CodeIgniter session data.
     */
    protected function unserializeSessionData(string $data): mixed
    {
        $unserialized = @unserialize(stripslashes($data));

        if (is_array($unserialized)) {
            // Convert {{slash}} markers back to actual slashes (CI convention)
            foreach ($unserialized as $key => $val) {
                if (is_string($val)) {
                    $unserialized[$key] = str_replace('{{slash}}', '\\', $val);
                }
            }
        }

        return $unserialized;
    }

    /**
     * Attempt to authenticate from the persistent login cookie.
     *
     * Cookie format: user_id;series;token
     */
    protected function authenticateFromPersistentLogin(Request $request): bool
    {
        $rememberCookie = $request->cookie('skiv_remember');

        if (!$rememberCookie) {
            return false;
        }

        $parts = explode(';', $rememberCookie);

        if (count($parts) !== 3) {
            return false;
        }

        [$userId, $series, $token] = $parts;

        // Look up the persistent login record
        $persistentLogin = DB::table('persistent_logins')
            ->where('user_id', $userId)
            ->where('series', $series)
            ->first();

        if (!$persistentLogin) {
            return false;
        }

        // Check token match
        if ($persistentLogin->token != $token) {
            // Token mismatch = potential replay attack
            Log::warning('Persistent login token mismatch - potential replay attack', [
                'user_id' => $userId,
            ]);
            return false;
        }

        return $this->loginUser((int) $userId);
    }
}
