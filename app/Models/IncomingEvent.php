<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
class IncomingEvent extends Model
{
    //
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'payload'
    ];

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            if (empty($event->id)) {
                $event->id = 'in_' . Str::ulid();
            }
        });
    }

    /****** Eloquent Relationship ******/
    public function delivery(): HasOne
    {
        return $this->hasOne(IncomingEventDelivery::class)->latestOfMany();
    }
}
