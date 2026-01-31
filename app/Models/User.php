<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

/**
 * User model for Skivsamlingen.
 *
 * Maps to the existing 'users' table.
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
        // Unused fields (exist in schema for backwards compatibility)
        'level',       // User permission level (1=normal, higher=admin)
        'last_import', // Timestamp of last Discogs import
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
            // Unused fields
            'level' => 'integer',
            'last_import' => 'integer',
        ];
    }

    /**
     * Encrypt password using the legacy algorithm.
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

    /**
     * Get the user's record collection.
     */
    public function records(): BelongsToMany
    {
        return $this->belongsToMany(Record::class, 'records_users')
            ->withPivot('id', 'comment');
    }

    /**
     * Get the user's records with sorting and optional pagination.
     *
     * @param string $order Sort field: 'artist', 'format', or 'year'
     * @param string $direction Sort direction: 'asc' or 'desc'
     * @param int $limit Number of records to return (0 for all)
     * @param int $offset Starting offset for pagination
     */
    public function getRecordsSorted(string $order = 'artist', string $direction = 'asc', int $limit = 0, int $offset = 0): \Illuminate\Database\Eloquent\Collection
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        // Pre-compute artist counts for this user in a single grouped query
        $artistCounts = $this->records()
            ->select('records.artist_id', DB::raw('COUNT(*) as num_records'))
            ->groupBy('records.artist_id');

        $query = $this->records()
            ->with('artist')
            ->join('artists', 'records.artist_id', '=', 'artists.id')
            ->joinSub($artistCounts, 'artist_counts', function ($join) {
                $join->on('records.artist_id', '=', 'artist_counts.artist_id');
            })
            ->select([
                'records.*',
                'artists.name as artist_name',
                'artists.id as artist_id',
                'artist_counts.num_records',
            ]);

        // Apply sorting with "The " prefix handling
        $artistSort = "TRIM(LEADING 'The ' FROM artists.name)";

        switch ($order) {
            case 'format':
                $query->orderByRaw("records.format {$direction}, {$artistSort} {$direction}, records.title {$direction}, records.year {$direction}");
                break;
            case 'year':
                $query->orderByRaw("records.year {$direction}, {$artistSort} {$direction}, records.title {$direction}");
                break;
            default: // artist
                $query->orderByRaw("{$artistSort} {$direction}, records.year ASC, records.title {$direction}");
        }

        if ($limit > 0) {
            $query->offset($offset)->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get the user's donations.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Check if the user is a supporter (donated >= 100 SEK within the last year).
     */
    public function isSupporter(): bool
    {
        return $this->donations()
            ->where('donated_at', '>=', now()->subYear())
            ->where('amount', '>=', 100)
            ->exists();
    }

    /**
     * Get the count of records in the user's collection.
     */
    public function getRecordsCount(): int
    {
        return $this->records()->count();
    }

    /**
     * Get the user's top artists by record count using the user's records relation.
     */
    public function getTopArtists(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->records()
            ->join('artists', 'records.artist_id', '=', 'artists.id')
            ->whereNotIn('artists.name', ['Various', 'V/A'])
            ->groupBy('artists.id')
            ->selectRaw('artists.name, COUNT(records.id) as records_count')
            ->orderByDesc('records_count')
            ->orderBy('artists.name')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return (object)[
                    'name' => $row->name,
                    'records' => $row->records_count,
                ];
            });
    }

    /**
     * Get the user's most recently added records.
     */
    public function getLatestRecords(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->records()
            ->with('artist')
            ->orderByDesc('records_users.id')
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                return (object)[
                    'title' => $record->title,
                    'name' => $record->artist->name ?? null,
                ];
            });
    }

    /**
     * Get the display label for the user's sex/gender.
     */
    public function getSexDisplayAttribute(): ?string
    {
        return match ($this->sex) {
            'f' => 'Kvinna',
            'm' => 'Man',
            default => null,
        };
    }

    /**
     * Search users by username or name.
     */
    public static function search(string $query, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('username', 'LIKE', "%{$query}%")
            ->orWhere('name', 'LIKE', "%{$query}%")
            ->orderBy('username')
            ->limit($limit)
            ->get();
    }
}
