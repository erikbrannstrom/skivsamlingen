<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for all Skivsamlingen models.
 *
 * Disables timestamps since the existing database schema doesn't use
 * Laravel's created_at/updated_at columns.
 */
abstract class BaseModel extends Model
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;
}
