<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'amount', 'donated_at'];

    protected function casts(): array
    {
        return [
            'donated_at' => 'date',
            'amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
