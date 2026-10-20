<?php

namespace App\Actions;

use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Models\InternalApiClient;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Models\WebhookEndpointSnapshot;
use App\Domain\Webhooks\Models\WebhookEvent;
use App\Models\ApiClient;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Ingestion pipeline for POST /api/internal/v1/webhook-events (spec §7.3):
 * duplicate detection → persist event → persist endpoint snapshot →
 * create delivery → queue delivery job.
 */
class AcceptMemberEvent
{
    /**
     * @param array $data Validated request data (AcceptWebhookEventRequest)
     * @return array{duplicate: bool, event: WebhookEvent, delivery: ?WebhookDelivery}
     */
    public function handle(array $data): array
    {
        /****** Idempotency: source system + instance + event ID + legacy webhook ID ******/
        $existing = $this->findExisting($data);

        if ($existing !== null) {
            return $existing;
        }

        try {

            $clientId = request()->header('X-SGDP-Client-Id');
            $clientSource = request()->header('X-SGDP-Source');
            $client = ApiClient::active()->where('client_id', $clientId)->where('name', $clientSource)->first();

            [$event, $delivery] = DB::transaction(function () use ($data, $client) {
                $snapshot = WebhookEndpointSnapshot::create([
                    'internal_api_client_id' => $client->id,
                    'tenant_id' => $data['tenant']['id'],
                    'legacy_webhook_id' => $data['endpoint']['legacy_webhook_id'],
                    'name' => $data['endpoint']['name'],
                    'url' => $data['endpoint']['url'],
                    'signing_secret_encrypted' => $data['endpoint']['signing_secret'],
                    'timeout_seconds' => $data['endpoint']['timeout_seconds'] ?? config('webhook.default_timeout', 10),
                    'max_attempts' => $data['endpoint']['max_attempts'] ?? config('webhook.default_max_attempts', 8),
                    'status' => $data['endpoint']['status'],
                    'alert_configuration' => $data['alert'] ?? null,
                    'configuration_hash' => $this->configurationHash($data),
                ]);

                $event = WebhookEvent::create([
                    'internal_api_client_id' => $client->id,
                    'tenant_id' => $data['tenant']['id'],
                    'tenant_code' => $data['tenant']['code'] ?? null,
                    'merchant_id' => $data['merchant']['id'] ?? null,
                    'merchant_name' => $data['merchant']['name'] ?? null,
                    'source_system' => $data['source']['system'],
                    'source_instance_id' => $data['source']['instance_id'],
                    'source_event_id' => $data['source']['event_id'],
                    'legacy_webhook_id' => $data['endpoint']['legacy_webhook_id'],
                    'event_type' => $data['event']['type'],
                    'api_version' => $data['event']['api_version'],
                    'aggregate_type' => $data['event']['aggregate_type'],
                    'aggregate_id' => $data['event']['aggregate_id'],
                    'resource_version' => $data['event']['resource_version'] ?? 1,
                    'occurred_at' => $data['source']['occurred_at'],
                    'payload' => $data['payload'],
                ]);

                $delivery = WebhookDelivery::create([
                    'internal_api_client_id' => $client->id,
                    'tenant_id' => $data['tenant']['id'],
                    'webhook_event_id' => $event->id,
                    'endpoint_snapshot_id' => $snapshot->id,
                    'status' => WebhookDelivery::STATUS_PENDING,
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

        DeliverWebhookJob::dispatch($delivery->id)->onQueue('webhook-delivery');

        $delivery->update(['status' => WebhookDelivery::STATUS_QUEUED]);

        return [
            'duplicate' => false,
            'event' => $event,
            'delivery' => $delivery,
        ];
    }

    protected function findExisting(array $data): ?array
    {
        $event = WebhookEvent::with('delivery')
            ->where('source_system', $data['source']['system'])
            ->where('source_instance_id', $data['source']['instance_id'])
            ->where('source_event_id', $data['source']['event_id'])
            ->where('legacy_webhook_id', $data['endpoint']['legacy_webhook_id'])
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

    /**
     * Hash of the endpoint configuration (excluding the secret) so later
     * configuration changes are detectable without storing plaintext.
     */
    protected function configurationHash(array $data): string
    {
        return hash('sha256', json_encode([
            'url' => $data['endpoint']['url'],
            'timeout_seconds' => $data['endpoint']['timeout_seconds'] ?? config('webhook.default_timeout', 10),
            'max_attempts' => $data['endpoint']['max_attempts'] ?? config('webhook.default_max_attempts', 8),
            'status' => $data['endpoint']['status'],
            'alert' => $data['alert'] ?? null,
        ], JSON_UNESCAPED_SLASHES));
    }
}
