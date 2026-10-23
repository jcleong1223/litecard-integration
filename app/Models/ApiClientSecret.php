<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiClientSecret extends Model
{
    //
    protected $fillable = [
        'api_client_id',
        'direction',
        'key_id',
        'secret_encrypted',
        'status',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'secret_encrypted',
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    CONST STATUS_ACTIVE = 'active';
    CONST STATUS_INACTIVE = 'inactive';

}
