<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LitecardCredential extends Model
{
    //

    protected $guarded = ['id'];

    CONST STATUS_UNVERIFIED = 'unverified';
    CONST STATUS_VERIFIED = 'verified';
    CONST STATUS_INVALID = 'invalid';
    CONST STATUS_DISABLED = 'disabled';

    public function scopeUnverified($query)
    {
        return $query->where('status', self::STATUS_UNVERIFIED);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    public function scopeInvalid($query)
    {
        return $query->where('status', self::STATUS_INVALID);
    }

    public function scopeDisabled($query)
    {
        return $query->where('status', self::STATUS_DISABLED);
    }
}
