<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class IncomingEventDelivery extends Model
{
    //
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [ 'id' ];

    const STATUS_PENDING = 'pending';
    const STATUS_QUEUED = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_RETRY_SCHEDULED = 'retry_scheduled';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED_PERMANENTLY = 'failed_permanently';
    const STATUS_CANCELLED = 'cancelled';

    protected static function booted(): void
    {
        static::creating(function (self $delivery) {
            if (empty($delivery->id)) {
                $delivery->id = 'idlv_' . Str::ulid();
            }
        });
    }

    /****** Eloquent Relationship ******/
    public function incomingEvent()
    {
        return $this->belongsTo(IncomingEvent::class);
    }

    public function transitionToProcessing(): bool
    {
        $affected = static::where('id', $this->id)
            ->whereIn('status', [
                self::STATUS_PENDING,
                self::STATUS_QUEUED,
                self::STATUS_RETRY_SCHEDULED,
            ])
            ->update([
                'status'           => self::STATUS_PROCESSING,
                'attempt_count'    => DB::raw('attempt_count + 1'),
                'first_attempt_at' => DB::raw('COALESCE(first_attempt_at, NOW())'),
                'last_attempt_at'  => now(),
            ]);

        if ($affected > 0) {
            $this->refresh();
            return true;
        }

        return false;
    }
}
