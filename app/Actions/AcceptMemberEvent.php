<?php

namespace App\Actions;

use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Models\InternalApiClient;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Models\WebhookEndpointSnapshot;
use App\Domain\Webhooks\Models\WebhookEvent;
use App\Enums\EventOperation;
use App\Jobs\DeliverLitecardEventJob;
use App\Models\ApiClient;
use App\Models\IncomingEvent;
use App\Models\IncomingEventDelivery;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;


class AcceptMemberEvent
{

    public function handle(array $data): array
    {
        /****** Idempotency: source system + instance + event ID ******/
        $existing = $this->findExisting($data);

        if ($existing !== null) {
            return $existing;
        }

        try {

            $clientId = request()->header('X-SGDPLC-Client-Id');
            $clientSource = request()->header('X-SGDPLC-Source');
            $client = ApiClient::active()->with('inboundSecret')->where('client_id', $clientId)->where('name', $clientSource)->first();

            if(!$client)
            {
                throw new InvalidArgumentException(
                    "Invalid api client: {$clientId} ({$clientSource})"
                );
            }


            [$event, $delivery] = DB::transaction(function () use ($data, $client) {

                $operation = EventOperation::tryFrom($data['event']['type'])?->operation();

                if ($operation === null) {
                    throw new InvalidArgumentException(
                        "Unsupported event type: {$data['event']['type']}"
                    );
                }

                $event = IncomingEvent::create([
                    'api_client_id' => $client->id,
                    'tenant_id' => $data['tenant']['id'],
                    'tenant_code' => $data['tenant']['code'] ?? null,
                    'outlet_id' => $data['outlet']['outlet_id'] ?? null,
                    'outlet_name' => $data['outlet']['outlet_name'] ?? null,
                    'source_system' => $data['source']['system'],
                    'source_instance_id' => $data['source']['instance_id'],
                    'source_event_id' => $data['source']['event_id'],
                    'event_type' => $data['event']['type'],
                    'api_version' => $data['event']['api_version'],
                    'aggregate_type' => $data['event']['aggregate_type'],
                    'aggregate_id' => $data['event']['aggregate_id'],
                    'resource_version' => $data['event']['resource_version'] ?? 1,
                    'occurred_at' => $data['source']['occurred_at'],
                    'payload' => $data['payload'],
                ]);

                $delivery = IncomingEventDelivery::create([
                    'api_client_id' => $client->id,
                    'tenant_id' => $data['tenant']['id'],
                    'incoming_event_id' => $event->id,
                    'operation' => $operation,
                    'status' => IncomingEventDelivery::STATUS_PENDING,
                ]);

                return [$event, $delivery];
            });
        } catch (QueryException $e) {

            $existing = $this->findExisting($data);

            if ($existing !== null) {
                return $existing;
            }

            throw $e;
        }

        DeliverLitecardEventJob::dispatch($delivery->id)->onQueue('litecard-event-delivery');

        $delivery->update(['status' => IncomingEventDelivery::STATUS_QUEUED]);

        return [
            'duplicate' => false,
            'event' => $event,
            'delivery' => $delivery,
        ];
    }

    protected function findExisting(array $data): ?array
    {
        $event = IncomingEvent::with('delivery')
            ->where('source_system', $data['source']['system'])
            ->where('source_instance_id', $data['source']['instance_id'])
            ->where('source_event_id', $data['source']['event_id'])
            ->first();

        if ($event === null) {
            return null;
        }

        return [
            'duplicate' => true,
            'event' => $event,
            'delivery' => $event->delivery,
        ];
    }
}
