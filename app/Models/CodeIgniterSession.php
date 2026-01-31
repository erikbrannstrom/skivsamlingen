<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodeIgniterSession extends Model
{
    public $timestamps = false;

    protected $table = 'ci_sessions';

    protected $primaryKey = 'session_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['session_id', 'ip_address', 'user_agent', 'last_activity', 'user_data'];
}
