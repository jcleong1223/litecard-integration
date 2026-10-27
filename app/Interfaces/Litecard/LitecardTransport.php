<?php

namespace App\Interfaces\Litecard;

/**
 * Performs the outbound HTTP POST to a merchant endpoint (spec §18).
 *
 * Implementations must NEVER throw — all network-level failures are
 * captured in the returned result array so that the orchestrator
 * (Actions/DeliverWebhook) can record the attempt and decide on retries.
 */
interface LitecardTransport
{
    /**
     * @param  string $url             Merchant endpoint URL.
     * @param  string $body            Raw JSON body.
     * @param  array  $headers         Request headers (incl. X-SGDP-*).
     * @param  int    $connectTimeout  Connection timeout in seconds (spec §18 default 3).
     * @param  int    $timeout         Total request timeout in seconds (spec §18 default 10).
     * @return array{
     *     http_status: int|null,
     *     response_headers: array,
     *     response_body: string|null,
     *     duration_ms: int,
     *     error_code: string|null,
     *     error_message: string|null,
     * }
     */
    public function send(string $url, string $body, array $headers, int $connectTimeout, int $timeout, string $method): array;
}