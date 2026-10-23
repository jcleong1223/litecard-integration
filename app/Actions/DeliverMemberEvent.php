<?php

namespace App\Domain\Webhooks\Actions;

use App\Domain\Webhooks\Contracts\WebhookSigner;
use App\Domain\Webhooks\Contracts\WebhookTransport;
use App\Domain\Webhooks\Enums\WebhookAlertType;
use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Jobs\SendWebhookAlertJob;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Models\WebhookDeliveryAttempt;
use App\Domain\Webhooks\Models\WebhookEndpointSnapshot;
use App\Domain\Webhooks\Policies\WebhookRetryPolicy;
use App\Domain\Webhooks\Policies\WebhookAlertPolicy;
use App\Domain\Webhooks\Services\EndpointHealthService;
use App\Domain\Webhooks\Services\WebhookPayloadBuilder;
use App\Models\IncomingEventDelivery;
use Illuminate\Support\Facades\Log;


class DeliverMemberEvent
{
    public function __construct(
        protected readonly WebhookSigner            $signer,
        protected readonly WebhookTransport         $transport,
        protected readonly WebhookPayloadBuilder    $payloadBuilder,
        protected readonly WebhookRetryPolicy       $retryPolicy,
        protected readonly WebhookAlertPolicy       $alertPolicy,
        protected readonly EndpointHealthService    $endpointHealthService,
    ) {}

    public function handle(IncomingEventDelivery $delivery): void
    {
        // §20.4 — atomic state transition; exit if another worker owns this delivery.
        if (! $delivery->transitionToProcessing()) {
            return;
        }

        $event    = $delivery->incomingEvent;

        if ($event === null) {
            Log::error('DeliverWebhook: event is null.', [
                'delivery_id' => $delivery->id,
            ]);
            $this->markFailedPermanently(
                $delivery,
                null,
                'unexpected_delivery_exception',
                'Event or endpoint snapshot is missing.',
                0,
            );
            return;
        }

        // ── Build envelope (§11.1) ────────────────────────────────────────
        $envelope = $this->payloadBuilder->build($event);
        $body     = json_encode($envelope, JSON_UNESCAPED_SLASHES);

        // ── Sign (§13) ────────────────────────────────────────────────────
        // The 'encrypted' cast on WebhookEndpointSnapshot decrypts the secret
        // on read; a manual Crypt::decryptString() here would double-decrypt.
        try {
            $secret = $snapshot->signing_secret_encrypted;
        } catch (\Throwable $e) {
            Log::error('DeliverWebhook: signing secret could not be decrypted.', [
                'delivery_id' => $delivery->id,
            ]);
            $this->markFailedPermanently(
                $delivery,
                null,
                'unexpected_delivery_exception',
                'Endpoint signing secret could not be decrypted.',
                0,
            );
            return;
        }
        $signing = $this->signer->sign($body, $secret);

        // ── Assemble outgoing headers (§12) ──────────────────────────────
        $requestHeaders = [
            'Content-Type'      => 'application/json',
            'User-Agent'        => 'SGDataPOS-Webhooks/1.0',
            'X-SGDP-Event-Id'   => $event->id,
            'X-SGDP-Event-Type' => $event->event_type,
            'X-SGDP-Delivery-Id' => $delivery->id,
            'X-SGDP-Attempt'    => $delivery->attempt_count,
            'X-SGDP-Timestamp'  => $signing['timestamp'],
            'X-SGDP-Signature'  => $signing['signature'],
        ];

        // ── Send (§18) ────────────────────────────────────────────────────
        $result = $this->transport->send(
            $snapshot->url,
            $body,
            $requestHeaders,
            config('webhook.connection_timeout', 3),
            $snapshot->timeout_seconds ?? config('webhook.default_timeout', 10),
        );

        // ── Record attempt (§19.4) ────────────────────────────────────────
        $this->recordAttempt($delivery, $requestHeaders, $body, $result);

        // ── Resolve delivery state ────────────────────────────────────────
        $this->resolveDeliveryState($delivery, $snapshot, $result);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Attempt recording
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Persist a row in webhook_delivery_attempts (spec §19.4).
     */
    protected function recordAttempt(
        WebhookDelivery $delivery,
        array           $requestHeaders,
        string          $body,
        array           $result,
    ): void {
        WebhookDeliveryAttempt::create([
            'tenant_id'                  => $delivery->tenant_id,
            'delivery_id'                => $delivery->id,
            'attempt_number'             => $delivery->attempt_count,
            'request_timestamp'          => now(),
            'request_headers_sanitized'  => $requestHeaders,
            'request_body_hash'          => hash('sha256', $body),
            'response_status'            => $result['http_status'],
            'response_headers_sanitized' => $result['response_headers'],
            'response_body_excerpt'      => $result['response_body'],
            'duration_ms'                => $result['duration_ms'],
            'error_type'                 => $result['error_code'],
            'error_message'              => $result['error_message'],
            'attempted_at'               => now(),
        ]);
    }

    /**
     * Determine the outcome and update the delivery accordingly.
     *
     * Outcomes:
     *  - 2xx                        → delivered           (spec §17)
     *  - retryable + attempts < max → retry_scheduled     (spec §16.1)
     *  - non-retryable or max hit   → failed_permanently  (spec §16.4)
     */
    protected function resolveDeliveryState(
        WebhookDelivery         $delivery,
        WebhookEndpointSnapshot $snapshot,
        array                   $result,
    ): void {
        $httpStatus = $result['http_status'];
        $errorCode  = $result['error_code'];

        // ── Success: 2xx (§17 - Any HTTP status from 200 to 299 is considered successful.) ────────────────────────────────────────────
        if ($httpStatus !== null && $httpStatus >= 200 && $httpStatus < 300) {
            $delivery->update([
                'status'                    => WebhookDelivery::STATUS_DELIVERED,
                'delivered_at'              => now(),
                'last_http_status'          => $httpStatus,
                'last_error_code'           => null,
                'last_error_message'        => null,
                'last_response_duration_ms' => $result['duration_ms'],
            ]);

            $this->endpointHealthService->recordEndpointHealthStatus($delivery);

            return;
        }

        // ── Derive error code for HTTP-level failures ─────────────────────
        if ($httpStatus !== null && $errorCode === null) {
            $errorCode = $this->retryPolicy->httpStatusToErrorCode($httpStatus);
        }

        $attemptCount = $delivery->attempt_count;
        $maxAttempts  = $snapshot->max_attempts ?? config('webhook.default_max_attempts', 8);

        // ── Retryable (§16.1 / §16.3) ─────────────────────────────────────
        if ($this->retryPolicy->shouldRetry($attemptCount, $maxAttempts, $httpStatus, $errorCode)) {
            $delay       = $this->retryPolicy->getDelaySeconds($attemptCount);
            $nextAttempt = now()->addSeconds($delay);

            $delivery->update([
                'status'                    => WebhookDelivery::STATUS_RETRY_SCHEDULED,
                'next_attempt_at'           => $nextAttempt,
                'last_http_status'          => $httpStatus,
                'last_error_code'           => $errorCode,
                'last_error_message'        => $result['error_message'],
                'last_response_duration_ms' => $result['duration_ms'],
            ]);

            DeliverWebhookJob::dispatch($delivery->id)
                ->onQueue('webhook-delivery')
                ->delay($nextAttempt);

            // TODO (Phase 5): check warning-alert threshold (spec §21.1).
            $this->endpointHealthService->recordEndpointHealthStatus($delivery);

            return;
        }

        // ── Permanently failed (§16.4 / §21.2) ───────────────────────────
        $this->markFailedPermanently(
            $delivery,
            $httpStatus,
            $errorCode,
            $result['error_message'],
            $result['duration_ms'],
        );

        // ── Update endpoint health (§19.5 / §21.3–§21.5) ─────────────────
        // Called once after the delivery state is fully resolved.
        $this->endpointHealthService->recordEndpointHealthStatus($delivery);
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
    ): void {
        $delivery->update([
            'status'                    => IncomingEventDelivery::STATUS_FAILED_PERMANENTLY,
            'failed_at'                 => now(),
            'last_http_status'          => $httpStatus,
            'last_error_code'           => $errorCode,
            'last_error_message'        => $errorMessage,
            'last_response_duration_ms' => $durationMs,
        ]);

        /****** dispatch endpoint_degraded alert (§21.2). ******/
        // $alertDecision = $this->alertPolicy->shouldAlert($delivery, AlertType::DELIVERY_PERMANENT_FAILURE->value);

        // if ($alertDecision['should_send']) {
        //     SendWebhookAlertJob::dispatch(
        //         $delivery->id,
        //         $alertDecision['recipients'],
        //         WebhookAlertType::DELIVERY_PERMANENT_FAILURE->value,
        //         $alertDecision['dedup_key'],
        //     )->onQueue('webhook-alerts');
        // }
    }
}
