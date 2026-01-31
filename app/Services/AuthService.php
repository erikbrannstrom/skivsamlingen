<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Authentication service for Skivsamlingen.
 *
 * Handles login, logout, and password operations.
 */
class AuthService
{
    /**
     * Attempt to login a user with username and password.
     *
     * @param string $username The username
     * @param string $password The plain-text password
     * @param bool $remember Whether to remember the user
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
            Auth::login($user, $remember);
            return true;
        }

        // Check SHA256 password
        if ($user->verifyPassword($password)) {
            Auth::login($user, $remember);
            return true;
        }

        return false;
    }

    /**
     * Logout the current user.
     */
    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    /**
     * Encrypt a password using the legacy algorithm.
     *
     * Format: sha256(md5(username)[0:12] + password + global_salt)
     */
    public function encryptPassword(string $username, string $password): string
    {
        return User::encryptPassword($username, $password);
    }

    /**
     * Verify a password against a user's stored hash.
     */
    public function verifyPassword(User $user, string $password): bool
    {
        return $user->verifyPassword($password);
    }
}
