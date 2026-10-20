<?php

namespace App\Services;

use App\Services\TikTok\Exceptions\TikTokApiException;
use App\Services\TikTok\Exceptions\TikTokAuthException;
use App\Services\TikTok\Exceptions\TikTokRateLimitException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokHttpClient
{
    protected string $apiBaseUrl;

    protected string $authBaseUrl;

    protected string $appKey;

    protected int $timeout;

    public function __construct(protected Signer $signer)
    {
        $this->apiBaseUrl = rtrim(config('tiktok.api_base_url'), '/');
        $this->authBaseUrl = rtrim(config('tiktok.auth_base_url'), '/');
        $this->appKey = config('tiktok.app_key');
        $this->timeout = config('tiktok.timeout', 30);
    }

    /**
     * Signed request against the Open API host (open-api.tiktokglobalshop.com).
     *
     * Automatically attaches: app_key, timestamp, sign (query params),
     * x-tts-access-token (header) and shop_cipher (query param) when provided.
     */
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
            fn ($value) => match (true) {
                is_array($value) => implode(',', $value),
                // PHP string-casts false to "" but http_build_query sends "0" —
                // normalize explicitly so the signed string matches the sent URL
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

        $url = $this->apiBaseUrl.$path;
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

    /**
     * Unsigned request against the Auth host (auth.tiktok-shops.com).
     * Used only for token/get and token/refresh, which authenticate
     * with app_key + app_secret directly instead of a signature.
     */
    public function authRequest(string $url, array $query): array
    {
        $start = microtime(true);

        $response = Http::acceptJson()
            ->timeout($this->timeout)
            ->connectTimeout(10)
            ->get($url, $query);

        $this->logCall('GET', $url, ['app_key' => $query['app_key'] ?? null], $response, $start);

        return $this->parseResponse($response, $url, isAuth: true);
    }

    protected function parseResponse(Response $response, string $path, bool $isAuth = false): array
    {
        $json = $response->json();

        if (! is_array($json)) {
            throw new TikTokApiException(
                "TikTok returned a non-JSON response from {$path}",
                httpStatus: $response->status(),
            );
        }

        $code = (int) ($json['code'] ?? -1);
        $message = $json['message'] ?? 'Unknown TikTok API error';
        $requestId = $json['request_id'] ?? null;

        if ($response->status() === 429) {
            throw new TikTokRateLimitException($message, $code, $requestId, 429);
        }

        // code 0 = business success
        if ($code !== 0) {
            $exception = $isAuth || in_array($code, [105000, 105001, 105002, 36009001, 36009002], true)
                ? TikTokAuthException::class
                : TikTokApiException::class;

            throw new $exception($message, $code, $requestId, $response->status());
        }

        return $json['data'] ?? [];
    }

    protected function logCall(string $method, string $path, array $query, Response $response, float $start): void
    {
        $json = $response->json();

        Log::info('TikTok API call', [
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
