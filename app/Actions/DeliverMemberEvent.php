<?php

namespace App\Actions;

use App\Domain\Webhooks\Models\WebhookDeliveryAttempt;
use App\Domain\Webhooks\Models\WebhookEndpointSnapshot;
use App\Enums\EventOperation;
use App\Enums\EventType;
use App\Interfaces\Litecard\LitecardTransport;
use App\Jobs\DeliverLitecardEventJob;
use App\Models\IncomingEventDelivery;
use App\Policies\RetryPolicy;
use App\Services\Litecard\PayloadMapperFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;


class DeliverMemberEvent
{
    public function __construct(
        protected readonly LitecardTransport        $transport,
        protected readonly PayloadMapperFactory     $payloadMapperFactory,
        protected readonly RetryPolicy              $retryPolicy,
    ) {}

    public function handle(IncomingEventDelivery $delivery): void
    {
        if (! $delivery->transitionToProcessing()) {
            return;
        }

        $event    = $delivery->incomingEvent;
        $clientLitecardCredential = $delivery->apiClient->litecardCredential ?? null;

        if($clientLitecardCredential === null)
        {
            $this->markFailedPermanently(
                $delivery,
                null,
                'litecard_credential_missing',
                'Client Litecard Credential is missing.',
                0,
                null
            );
            return;
        }

        if ($event === null) {
            Log::error('DeliverMemberEvent: event is null.', [
                'delivery_id' => $delivery->id,
            ]);
            $this->markFailedPermanently(
                $delivery,
                null,
                'unexpected_delivery_exception',
                'Event or endpoint snapshot is missing.',
                0,
                null
            );
            return;
        }

        $operationValue  = EventType::tryFrom($event->event_type)->operation()->value;
        $operation = EventOperation::tryFrom($operationValue);
        $method    = EventOperation::tryFrom($operationValue)->method();
        $envelope = [];
        if ($method !== 'GET') {
            $envelope = $this->payloadMapperFactory->make($operationValue)->toPayload($event);
        }
        $body = json_encode($envelope, JSON_UNESCAPED_SLASHES);
        $endpoint = rtrim(config('litecard.api_base_url'), '/') . $operation->endpoint();

        if ($operation === EventOperation::CARD_VIEW) {
            $payload = $event->payload;
            $cardId = $payload['litecard']['card_id'] ?? null;
            $body = '';
            $endpoint .= '/' . rawurlencode((string) $cardId);
        }

        $requestHeaders = [
            'Content-Type'      => 'application/json',
            'Authorization'     => 'Bearer ' . Crypt::decryptString($clientLitecardCredential->access_token_encrypted),
        ];

        $result = $this->transport->send(
            $endpoint,
            $body,
            $requestHeaders,
            config('sourcesystem.connection_timeout', 3),
            $snapshot->timeout_seconds ?? config('sourcesystem.default_timeout', 10),
            $operation->method() ?? 'POST',
        );

        // ── Record attempt ────────────────────────────────────────
        // $this->recordAttempt($delivery, $requestHeaders, $body, $result);

        // ── Resolve delivery state ────────────────────────────────────────
        $this->resolveDeliveryState($delivery, $result);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Attempt recording
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Persist a row in webhook_delivery_attempts.
     */
    // protected function recordAttempt(
    //     IncomingEventDelivery $delivery,
    //     array           $requestHeaders,
    //     string          $body,
    //     array           $result,
    // ): void {
    //     WebhookDeliveryAttempt::create([
    //         'tenant_id'                  => $delivery->tenant_id,
    //         'delivery_id'                => $delivery->id,
    //         'attempt_number'             => $delivery->attempt_count,
    //         'request_timestamp'          => now(),
    //         'request_headers_sanitized'  => $requestHeaders,
    //         'request_body_hash'          => hash('sha256', $body),
    //         'response_status'            => $result['http_status'],
    //         'response_headers_sanitized' => $result['response_headers'],
    //         'response_body_excerpt'      => $result['response_body'],
    //         'duration_ms'                => $result['duration_ms'],
    //         'error_type'                 => $result['error_code'],
    //         'error_message'              => $result['error_message'],
    //         'attempted_at'               => now(),
    //     ]);
    // }

    /**
     * Determine the outcome and update the delivery accordingly.
     *
     * Outcomes:
     *  - 2xx                        → delivered
     *  - retryable + attempts < max → retry_scheduled
     *  - non-retryable or max hit   → failed_permanently
     */
    protected function resolveDeliveryState(
        IncomingEventDelivery   $delivery,
        array                   $result,
    ): void {
        $httpStatus = $result['http_status'];
        $errorCode  = $result['error_code'];

        // ── Success: 2xx ────────────────────────────────────────────
        if ($httpStatus !== null && $httpStatus >= 200 && $httpStatus < 300) {
            $delivery->update([
                'status'                    => IncomingEventDelivery::STATUS_DELIVERED,
                'delivered_at'              => now(),
                'last_http_status'          => $httpStatus,
                'last_error_code'           => null,
                'last_error_message'        => null,
                'last_response_duration_ms' => $result['duration_ms'],
                'response_body_excerpt'      => $this->decodeExcerpt($result['response_body'])
            ]);

            return;
        }

        // ── Derive error code for HTTP-level failures ─────────────────────
        if ($httpStatus !== null && $errorCode === null) {
            $errorCode = $this->retryPolicy->httpStatusToErrorCode($httpStatus);
        }

        $attemptCount = $delivery->attempt_count;
        $maxAttempts  = config('sourcesystem.default_max_attempts', 8);

        // ── Can Retry ─────────────────────────────────────
        if ($this->retryPolicy->shouldRetry($attemptCount, $maxAttempts, $httpStatus, $errorCode)) {
            $delay       = $this->retryPolicy->getDelaySeconds($attemptCount);
            $nextAttempt = now()->addSeconds($delay);

            $delivery->update([
                'status'                    => IncomingEventDelivery::STATUS_RETRY_SCHEDULED,
                'next_attempt_at'           => $nextAttempt,
                'last_http_status'          => $httpStatus,
                'last_error_code'           => $errorCode,
                'last_error_message'        => $result['error_message'],
                'last_response_duration_ms' => $result['duration_ms'],
                'response_body_excerpt'     => $this->decodeExcerpt($result['response_body'])
            ]);

            DeliverLitecardEventJob::dispatch($delivery->id)
                ->onQueue('litecard-event-delivery')
                ->delay($nextAttempt);

            return;
        }

        // ── Permanently failed ───────────────────────────
        $this->markFailedPermanently(
            $delivery,
            $httpStatus,
            $errorCode,
            $result['error_message'],
            $result['duration_ms'],
            $result['response_body'],
        );
    }

    /**
     * Mark the delivery as failed_permanently and trigger a final alert.
     */
    protected function markFailedPermanently(
        IncomingEventDelivery $delivery,
        ?int            $httpStatus,
        ?string         $errorCode,
        ?string         $errorMessage,
        int             $durationMs,
        ?string         $responseBody,
    ): void {
        $delivery->update([
            'status'                    => IncomingEventDelivery::STATUS_FAILED_PERMANENTLY,
            'failed_at'                 => now(),
            'last_http_status'          => $httpStatus,
            'last_error_code'           => $errorCode,
            'last_error_message'        => $errorMessage,
            'last_response_duration_ms' => $durationMs,
            'response_body_excerpt'     => $this->decodeExcerpt($responseBody),
        ]);

    }

    protected function decodeExcerpt(?string $body): ?array
    {
        if ($body === null || $body === '') {
            return null;
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : ['value' => $decoded];
        } catch (\JsonException) {
            return ['raw' => $body];
        }
    }
}
