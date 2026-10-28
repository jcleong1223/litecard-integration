<?php

namespace App\Services;

use App\Models\ApiClient;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ClientNotifier
{
    /**
     * POST the envelope to the client's registered callback URL.
     *
     * @param  array<string,mixed> $envelope Payload to deliver (already serializable).
     * @throws RuntimeException when the callback URL is unreachable or answers non-2xx.
     */
    public function send(ApiClient $client, array $envelope): void
    {
        $callbackUrl = $client->callback_url;

        if (empty($callbackUrl)) {
            // Nothing configured — silently skip. The delivery state is still persisted.
            return;
        }

        $secret = $this->resolveCallbackSecret($client);

        if ($secret === null) {
            Log::error('ClientNotifier: callback secret missing/undecryptable.', [
                'client_id' => $client->client_id,
            ]);

            throw new RuntimeException('Callback secret is not available for client.');
        }

        $body      = json_encode($envelope, JSON_UNESCAPED_SLASHES);
        $timestamp = (string) now()->timestamp;
        $requestId = 'req_' . Str::lower(Str::random(16));
        $signature = 'v1=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        $headers = [
            'Content-Type'        => 'application/json',
            'X-SGDPLC-Client-Id'  => $client->client_id,
            'X-SGDPLC-Source'     => config('sourcesystem.callback_source', 'litecard-integration'),
            'X-SGDPLC-Timestamp'  => $timestamp,
            'X-SGDPLC-Request-Id' => $requestId,
            'X-SGDPLC-Signature'  => $signature,
        ];

        try {
            $response = Http::connectTimeout(config('sourcesystem.connection_timeout', 3))
                ->timeout(config('sourcesystem.default_timeout', 10))
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($callbackUrl);
        } catch (\Throwable $e) {
            Log::warning('ClientNotifier: callback transport failed.', [
                'client_id'   => $client->client_id,
                'delivery_id' => $envelope['delivery_id'] ?? null,
                'error'       => $e->getMessage(),
            ]);

            throw new RuntimeException('Callback transport failed: ' . $e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            Log::warning('ClientNotifier: callback answered non-2xx.', [
                'client_id'   => $client->client_id,
                'delivery_id' => $envelope['delivery_id'] ?? null,
                'http_status' => $response->status(),
            ]);

            throw new RuntimeException('Callback endpoint returned HTTP ' . $response->status() . '.');
        }
    }

    /**
     * Decrypt the active callback-direction secret for the client.
     */
    protected function resolveCallbackSecret(ApiClient $client): ?string
    {
        $encrypted = $client->callbackSecret?->secret_encrypted;

        if (empty($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }
}
