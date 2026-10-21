<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class IncomingEventDelivery extends Model
{
    //
    public $incrementing = false;

    protected $keyType = 'string';



    protected static function booted(): void
    {
        static::creating(function (self $delivery) {
            if (empty($delivery->id)) {
                $delivery->id = 'idlv_' . Str::ulid();
            }
        });
    }
}
