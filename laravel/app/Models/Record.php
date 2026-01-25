<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Record extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['artist_id', 'title', 'year', 'format'];

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'records_users')
            ->withPivot('id', 'comment');
    }
}
