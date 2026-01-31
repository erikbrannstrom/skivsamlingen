<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artist extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name'];

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    /**
     * Format artist name, moving "The " prefix to end for display.
     * e.g., "The Beatles" becomes "Beatles, The"
     */
    public function getDisplayNameAttribute(): string
    {
        if (stripos($this->name, 'the ') === 0) {
            return rtrim(substr($this->name, 4), ',') . ', ' . substr($this->name, 0, 3);
        }

        return $this->name;
    }
}
