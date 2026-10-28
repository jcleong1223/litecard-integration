<?php

namespace App\Jobs;

use App\Actions\NotifyMemberEvent;
use App\Models\IncomingEventDelivery;
use App\Services\ClientNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyClientResultJob implements ShouldQueue
{
    use Queueable;

    /** Retry the callback a few times with exponential backoff. */
    public int $tries = 5;

    /** @var array<int,int> seconds between retries */
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(
        public readonly string $deliveryId,
        public readonly string $status = IncomingEventDelivery::STATUS_DELIVERED,
    ) {}

    public function handle(ClientNotifier $notifier): void
    {

        $delivery = IncomingEventDelivery::with([
            'incomingEvent',
            'apiClient.callbackSecret',
        ])->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        $client = $delivery->apiClient;

        if ($client === null || empty($client->callback_url)) {
            // No callback configured for this client — nothing to notify.
            return;
        }

        $event = $delivery->incomingEvent;

        $envelope = [
            'delivery_id' => $delivery->id,
            'event_id'    => $event?->source_event_id,
            'operation'   => $delivery->operation,
            'status'      => $delivery->status,
            'http_status' => $delivery->last_http_status,
            'error_code'  => $delivery->last_error_code,
            'occurred_at' => $delivery->delivered_at ?? $delivery->failed_at,
            'data'        => $delivery->response_body_excerpt,
        ];

        $notifier->send($client, $envelope);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('NotifyClientResultJob: callback delivery failed permanently.', [
            'delivery_id' => $this->deliveryId,
            'error'       => $e->getMessage(),
        ]);
    }
}
