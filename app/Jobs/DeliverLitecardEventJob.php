<?php

namespace App\Jobs;

use App\Domain\Webhooks\Actions\DeliverMemberEvent;
use App\Models\IncomingEventDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliverLitecardEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;
    /**
     * Create a new job instance.
     */
    public function __construct(public readonly string $deliveryId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(DeliverMemberEvent $deliverMemberEvent): void
    {
        //
        $delivery = IncomingEventDelivery::with(['incomingEvent'])->find($this->deliveryId);

        if ($delivery === null) {
            // Delivery was deleted or cancelled before the worker picked it up — exit silently.
            return;
        }

        $deliverMemberEvent->handle($delivery);
    }
}
