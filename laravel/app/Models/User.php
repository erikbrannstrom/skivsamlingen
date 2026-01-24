<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User model for Skivsamlingen.
 *
 * Maps to the existing 'users' table from the CodeIgniter application.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Indicates if the model should be timestamped.
     * The existing schema doesn't use Laravel's timestamp columns.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password',
        'email',
        'public_email',
        'name',
        'birth',
        'about',
        'sex',
        'per_page',
        'registered',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth' => 'date',
            'registered' => 'datetime',
            'public_email' => 'boolean',
            'per_page' => 'integer',
        ];
    }

    /**
     * Get the records in this user's collection.
     */
    public function records()
    {
        return $this->belongsToMany(Record::class, 'records_users')
            ->withPivot('id', 'comment');
    }

    /**
     * Get the user's donations.
     */
    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Check if user is a supporter (donated >= 100 SEK in the past year).
     */
    public function isSupporter(): bool
    {
        $oneYearAgo = now()->subYear();

        return $this->donations()
            ->where('donated_at', '>=', $oneYearAgo)
            ->where('amount', '>=', 100)
            ->exists();
    }

    /**
     * Encrypt password using CodeIgniter's legacy algorithm.
     *
     * Format: sha256(md5(username)[0:12] + password + global_salt)
     */
    public static function encryptPassword(string $username, string $password): string
    {
        $presalt = substr(md5($username), 0, 12);
        return hash('sha256', $presalt . $password . config('auth.global_salt'));
    }

    /**
     * Verify a password against the stored hash.
     *
     * Supports both legacy SHA1 and current SHA256 formats.
     */
    public function verifyPassword(string $password): bool
    {
        // Check SHA256 format (current)
        if ($this->password === self::encryptPassword($this->username, $password)) {
            return true;
        }

        // Check legacy SHA1 format
        if ($this->password === sha1($password)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user has a legacy SHA1 password that needs upgrading.
     */
    public function hasLegacyPassword(string $password): bool
    {
        return $this->password === sha1($password);
    }

    /**
     * Upgrade a legacy SHA1 password to SHA256.
     */
    public function upgradePassword(string $password): void
    {
        $this->password = self::encryptPassword($this->username, $password);
        $this->save();
    }
}
