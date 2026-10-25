<?php

namespace App\Services;

use App\Services\Litecard\Exceptions\LitecardApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LitecardHttpClient
{
    protected string $apiBaseUrl;

    protected string $authBaseUrl;

    protected string $appKey;

    protected int $timeout;

    public function __construct()
    {
        $this->apiBaseUrl = rtrim(config('litecard.api_base_url'), '/');
        $this->timeout = config('litecard.timeout', 30);
    }

    public function request(
        string $method,
        string $path,
        array $query = [],
        ?array $body = null,
        ?string $accessToken = null,
        ?string $shopCipher = null,
    ): array {
        $method = strtoupper($method);

        $query['app_key'] = $this->appKey;
        $query['timestamp'] = time();

        if ($shopCipher) {
            $query['shop_cipher'] = $shopCipher;
        }

        $query = array_map(
            fn($value) => match (true) {
                is_array($value) => implode(',', $value),
                is_bool($value) => ($value ? 'true' : 'false'),
                default => $value,
            },
            $query,
        );

        $jsonBody = $body !== null ? json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

        $query['sign'] = $this->signer->sign($path, $query, $jsonBody);

        $headers = ['Content-Type' => 'application/json'];

        if ($accessToken) {
            $headers['x-tts-access-token'] = $accessToken;
        }

        $pending = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->connectTimeout(10)
            ->retry(2, 500, function ($exception) {
                // Retry only on connection errors and 5xx responses
                return $exception instanceof \Illuminate\Http\Client\ConnectionException
                    || ($exception instanceof \Illuminate\Http\Client\RequestException
                        && $exception->response->serverError());
            }, throw: false);

        $url = $this->apiBaseUrl . $path;
        $start = microtime(true);

        $response = match ($method) {
            'GET' => $pending->get($url, $query),
            'DELETE' => $pending->withQueryParameters($query)->delete($url, $body ?? []),
            'POST', 'PUT' => $pending->withQueryParameters($query)
                ->withBody($jsonBody ?? '{}', 'application/json')
                ->send($method, $url),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        $this->logCall($method, $path, $query, $response, $start);

        return $this->parseResponse($response, $path);
    }

    public function get(string $path, array $query = [], ?string $accessToken = null, ?string $shopCipher = null): array
    {
        return $this->request('GET', $path, $query, null, $accessToken, $shopCipher);
    }

    public function post(string $path, array $query = [], array $body = [], ?string $accessToken = null, ?string $shopCipher = null): array
    {
        return $this->request('POST', $path, $query, $body, $accessToken, $shopCipher);
    }

    public function authRequest(string $url, array $body = []): array
    {
        $start = microtime(true);

        $response = Http::acceptJson()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout($this->timeout)
            ->connectTimeout(10)
            ->post($url, $body);

        $this->logCall('POST', $url, $body ?? null, $response, $start);

        return $this->parseResponse($response, $url, isAuth: true);
    }

    protected function parseResponse(Response $response, string $path, bool $isAuth = false): array
    {
        $json = $response->json();

        if (! is_array($json)) {
            throw new LitecardApiException(
                "Litecard returned a non-JSON response from {$path}",
                httpStatus: $response->status(),
            );
        }

        $code = (int) $response->status();
        $message = 'Unknown Litecard API error';
        $requestId = null;

        if ($code !== 200) {
            $exception = LitecardApiException::class;

            throw new $exception($message, $code, $requestId, $response->status());
        }

        return $json ?? $json['data'];
    }

    protected function logCall(string $method, string $path, array $query, Response $response, float $start): void
    {
        $json = $response->json();

        Log::info('Litecard API call', [
            'method' => $method,
            'path' => $path,
            'query_keys' => array_keys($query),
            'http_status' => $response->status(),
            'code' => $json['code'] ?? null,
            'message' => $json['message'] ?? null,
            'request_id' => $json['request_id'] ?? null,
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
        ]);
    }
}
