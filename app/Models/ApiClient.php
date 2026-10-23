<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiClient extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [
        'name',
        'client_id',
        'allowed_ips',
        'callback_url',
        'status',
        'last_used_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'last_used_at' => 'datetime'
    ];

    CONST STATUS_ACTIVE = 'active';
    CONST STATUS_INACTIVE = 'inactive';

    /****** Scope function ******/
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /****** Eloquent Relationship function ******/
    public function secrets() : HasMany
    {
        return $this->hasMany(ApiClientSecret::class);
    }

    public function inboundSecret(): HasOne
    {
        return $this->hasOne(ApiClientSecret::class)->where('status', ApiClientSecret::STATUS_ACTIVE)->where('direction', 'inbound');
    }

    public function inboundSecretByKey(string $key): HasOne
    {
        return $this->hasOne(ApiClientSecret::class)->where('status', ApiClientSecret::STATUS_ACTIVE)->where('key_id', $key)->where('direction', 'inbound');
    }


    public function callbackSecret(): HasOne
    {
        return $this->hasOne(ApiClientSecret::class)->where('status', ApiClientSecret::STATUS_ACTIVE)->where('direction', 'callback');
    }

    public function callbackSecretByKey(string $key): HasOne
    {
        return $this->hasOne(ApiClientSecret::class)->where('status', ApiClientSecret::STATUS_ACTIVE)->where('key_id', $key)->where('direction', 'callback');
    }
}
