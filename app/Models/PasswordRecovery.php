<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PasswordRecovery model for password reset tokens.
 *
 * Maps to the existing 'password_recovery' table.
 */
class PasswordRecovery extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'password_recovery';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'username';

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'username',
        'hash',
        'created_on',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_on' => 'integer',
    ];

    /**
     * Token validity in seconds (48 hours).
     */
    public const TOKEN_VALIDITY_SECONDS = 48 * 60 * 60;

    /**
     * Get the user associated with this password recovery token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    /**
     * Check if the token is still valid (not expired).
     */
    public function isValid(): bool
    {
        return ($this->created_on + self::TOKEN_VALIDITY_SECONDS) > time();
    }

    /**
     * Check if the token has expired.
     */
    public function isExpired(): bool
    {
        return !$this->isValid();
    }

    /**
     * Generate a new unique hash for password recovery.
     */
    public static function generateHash(): string
    {
        return sha1(uniqid(mt_rand(), true));
    }

    /**
     * Clean up expired tokens.
     *
     * @return int Number of deleted tokens
     */
    public static function cleanupExpired(): int
    {
        return static::where('created_on', '<', time() - self::TOKEN_VALIDITY_SECONDS)
            ->delete();
    }

    /**
     * Find a valid (non-expired) token by username and hash.
     *
     * @param string $username The username
     * @param string $hash The recovery hash
     * @return static|null The recovery record or null if not found/expired
     */
    public static function findValid(string $username, string $hash): ?static
    {
        // Clean up expired tokens first
        static::cleanupExpired();

        return static::where('username', $username)
            ->where('hash', $hash)
            ->first();
    }

    /**
     * Check if a recovery request already exists for a user.
     *
     * @param string $username The username
     * @return bool True if a request already exists
     */
    public static function existsForUser(string $username): bool
    {
        return static::where('username', $username)->exists();
    }

    /**
     * Create a new password recovery token for a user.
     *
     * @param User $user The user
     * @return static The created recovery record
     */
    public static function createForUser(User $user): static
    {
        return static::create([
            'username' => $user->username,
            'hash' => static::generateHash(),
            'created_on' => time(),
        ]);
    }
}
