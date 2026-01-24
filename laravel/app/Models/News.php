<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * News model for site announcements.
 *
 * @property int $id
 * @property string $title
 * @property string $body
 * @property \DateTime $posted
 */
class News extends BaseModel
{
    use HasFactory;
    protected $table = 'news';

    protected $fillable = ['title', 'body', 'posted'];

    protected $casts = [
        'posted' => 'datetime',
    ];

    /**
     * Scope to order by posted date descending (newest first).
     */
    public function scopeNewest(Builder $query): Builder
    {
        return $query->orderBy('posted', 'desc');
    }
}
