<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersistentLogin extends Model
{
    public $timestamps = false;

    protected $table = 'persistent_logins';

    protected $fillable = ['user_id', 'series', 'token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
