<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OutgoingEvent extends Model
{
    //
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [];

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            if (empty($event->id)) {
                $event->id = 'out_' . Str::ulid();
            }
        });
    }
}