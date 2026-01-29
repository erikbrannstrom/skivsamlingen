<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\CodeIgniterSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to share authentication with CodeIgniter.
 *
 * This middleware reads CodeIgniter's session cookie and persistent login
 * cookie to authenticate users in Laravel, enabling both applications to
 * share the same authentication state during the migration period.
 */
class SharedAuth
{
    public function __construct(
        protected CodeIgniterSessionService $ciSession
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ciUserId = $this->ciSession->getUserId();

        if (Auth::check()) {
            // No CI session - don't interfere with Laravel auth (e.g., during tests)
            if ($ciUserId === null) {
                return $next($request);
            }

            // CI session matches Laravel
            if ($ciUserId === Auth::id()) {
                return $next($request);
            }

            // CI session mismatch - sync Laravel to CI state
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if (is_int($ciUserId)) {
                $this->loginUser($ciUserId);
            }
            return $next($request);
        }

        // Try CI session authentication
        if (is_int($ciUserId)) {
            $this->loginUser($ciUserId);
            return $next($request);
        }

        // Try persistent login cookie
        $this->authenticateFromPersistentLogin();

        return $next($request);
    }

    /**
     * Login a user by their ID.
     */
    protected function loginUser(int $userId): bool
    {
        $user = User::find($userId);

        if ($user) {
            Auth::login($user);
            return true;
        }

        return false;
    }

    /**
     * Attempt to authenticate from the persistent login cookie.
     */
    protected function authenticateFromPersistentLogin(): bool
    {
        $result = $this->ciSession->checkPersistentLogin();

        // Check for suspected cookie theft
        if ($result['theft_suspected'] && $result['user_id']) {
            $this->handleSuspectedTheft($result['user_id']);
            return false;
        }

        if (!$result['valid']) {
            return false;
        }

        $loggedIn = $this->loginUser($result['user_id']);

        if ($loggedIn) {
            // Rotate the token to prevent replay attacks
            $this->ciSession->rotatePersistentLoginToken($result['user_id'], $result['series']);
        }

        return $loggedIn;
    }

    /**
     * Handle suspected cookie theft by invalidating all user sessions.
     */
    protected function handleSuspectedTheft(int $userId): void
    {
        // Invalidate all sessions for this user
        $this->ciSession->invalidateAllUserSessions($userId);

        // Show warning to the user
        session()->flash('error', 'Av säkerhetsskäl har alla dina sessioner avslutats. Om du inte själv har försökt logga in från en annan enhet, bör du byta lösenord omedelbart.');
    }
}
