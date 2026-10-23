<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class OutgoingEventDelivery extends Model
{
    //
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (self $delivery) {
            if (empty($delivery->id)) {
                $delivery->id = 'odlv_' . Str::ulid();
            }
        });
    }
}
