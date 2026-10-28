<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class OutgoingEvent extends Model
{
    //
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [ 'id' ];

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            if (empty($event->id)) {
                $event->id = 'out_' . Str::ulid();
            }
        });
    }

    /****** Eloquent Relationship ******/
    public function outgoingDelivery(): HasOne
    {
        return $this->hasOne(OutgoingEventDelivery::class)->latestOfMany();
    }
}