<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Authentication service for Skivsamlingen.
 *
 * Handles login, logout, and password operations while maintaining
 * compatibility with CodeIgniter's session system during migration.
 */
class AuthService
{
    public function __construct(
        protected CodeIgniterSessionService $ciSession
    ) {}

    /**
     * Attempt to login a user with username and password.
     *
     * Supports both legacy SHA1 passwords (auto-upgrades to SHA256)
     * and current SHA256 passwords.
     *
     * @param string $username The username
     * @param string $password The plain-text password
     * @param bool $remember Whether to create a persistent login
     * @return bool True if login successful, false otherwise
     */
    public function login(string $username, string $password, bool $remember = false): bool
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            return false;
        }

        // Check legacy SHA1 password and auto-upgrade
        if ($user->hasLegacyPassword($password)) {
            $user->upgradePassword($password);
            $this->loginUser($user, $remember);
            return true;
        }

        // Check SHA256 password
        if ($user->verifyPassword($password)) {
            $this->loginUser($user, $remember);
            return true;
        }

        return false;
    }

    /**
     * Login a user and update CodeIgniter session.
     *
     * @param User $user The user to login
     * @param bool $remember Whether to create a persistent login
     */
    protected function loginUser(User $user, bool $remember = false): void
    {
        Auth::login($user);
        $this->ciSession->loginUser($user);

        if ($remember) {
            $this->ciSession->createPersistentLogin($user->id);
        }
    }

    /**
     * Logout the current user.
     *
     * Clears both Laravel and CodeIgniter session data.
     */
    public function logout(): void
    {
        // Clear CodeIgniter session
        $this->ciSession->logoutUser();

        // Clear persistent login cookie if present
        $this->ciSession->clearPersistentLogin();

        // Logout from Laravel
        Auth::logout();

        // Invalidate Laravel session
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    /**
     * Encrypt a password using the legacy algorithm.
     *
     * Format: sha256(md5(username)[0:12] + password + global_salt)
     *
     * @param string $username The username
     * @param string $password The plain-text password
     * @return string The encrypted password
     */
    public function encryptPassword(string $username, string $password): string
    {
        return User::encryptPassword($username, $password);
    }

    /**
     * Verify a password against a user's stored hash.
     *
     * @param User $user The user
     * @param string $password The plain-text password to verify
     * @return bool True if password matches
     */
    public function verifyPassword(User $user, string $password): bool
    {
        return $user->verifyPassword($password);
    }
}
