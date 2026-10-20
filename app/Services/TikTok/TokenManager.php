<?php

namespace App\Services\TikTok;

use App\Services\TikTok\Exceptions\TikTokAuthException;
use App\Services\TikTok\Resources\AuthService;
use Illuminate\Support\Facades\Cache;

/**
 * Cache-backed token + shop store.
 *
 * Storage layout:
 *   tiktok.shop.{shop_id}            -> full shop record incl. tokens
 *   tiktok.shops.index               -> list of all known shop ids (for cron sweeps)
 *   tiktok.shops.merchant.{ref}      -> list of shop ids per merchant_ref
 *
 * TODO: swap the cache for tiktok_shops + tiktok_auth_tokens tables
 * once migrations are created. Public method signatures should stay stable.
 */
class TokenManager
{
    /**
     * Refresh the access token when less than this many seconds remain.
     */
    public const REFRESH_THRESHOLD_SECONDS = 86400; // 24 hours

    public function __construct(protected AuthService $authService)
    {
    }

    /**
     * Persist one token set across all shops it authorizes.
     * Returns the number of shops stored.
     */
    public function saveTokenSet(array $tokens, array $shops, ?string $merchantRef = null): int
    {
        $count = 0;

        foreach ($shops as $shop) {
            $record = array_merge($this->shopRecord($shop), $this->tokenRecord($tokens), [
                'merchant_ref' => $merchantRef,
                'authorized_at' => now()->toIso8601String(),
            ]);

            Cache::forever($this->shopKey($shop['id']), $record);
            $this->indexShop($shop['id'], $merchantRef);
            $count++;
        }

        return $count;
    }

    /**
     * Get the stored record for a shop, or null if unknown/revoked.
     */
    public function shop(string $shopId): ?array
    {
        $record = Cache::get($this->shopKey($shopId));

        return is_array($record) ? $record : null;
    }

    /**
     * All shop records belonging to a merchant.
     */
    public function shopsForMerchant(string $merchantRef): array
    {
        return collect(Cache::get("tiktok.shops.merchant.{$merchantRef}", []))
            ->map(fn ($shopId) => $this->shop($shopId))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * All shop records across every merchant (used by the refresh cron).
     */
    public function allShops(): array
    {
        return collect(Cache::get('tiktok.shops.index', []))
            ->map(fn ($shopId) => $this->shop($shopId))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Get a usable access token for a shop, refreshing it first
     * when it is within REFRESH_THRESHOLD_SECONDS of expiry.
     *
     * Throws TikTokAuthException when the refresh token is also expired —
     * in that case the seller must re-authorize via the OAuth flow.
     */
    public function getAccessTokenForShop(string $shopId): string
    {
        $record = $this->shop($shopId)
            ?? throw new TikTokAuthException("Shop {$shopId} is not connected");

        if ($this->needsRefresh($record)) {
            $record = $this->refresh($shopId);
        }

        return $record['access_token'];
    }

    /**
     * Force-refresh the tokens for a shop. Persists the rotated
     * refresh_token — TikTok invalidates the previous one.
     */
    public function refresh(string $shopId): array
    {
        $record = $this->shop($shopId)
            ?? throw new TikTokAuthException("Shop {$shopId} is not connected");

        $tokens = $this->authService->refreshAccessToken($record['refresh_token']);

        $record = array_merge($record, $this->tokenRecord($tokens));

        Cache::forever($this->shopKey($shopId), $record);

        return $record;
    }

    /**
     * Whether a stored record should be refreshed now.
     */
    public function needsRefresh(array $record): bool
    {
        $expiresAt = $record['access_token_expires_at'] ?? null;

        if (! $expiresAt) {
            return true;
        }

        return ($expiresAt - time()) < self::REFRESH_THRESHOLD_SECONDS;
    }

    protected function tokenRecord(array $tokens): array
    {
        return [
            'access_token' => $tokens['access_token'],
            // *_expire_in are absolute unix timestamps in the v2 token API
            'access_token_expires_at' => $tokens['access_token_expire_in'] ?? null,
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'refresh_token_expires_at' => $tokens['refresh_token_expire_in'] ?? null,
            'open_id' => $tokens['open_id'] ?? null,
            'seller_name' => $tokens['seller_name'] ?? null,
            'seller_base_region' => $tokens['seller_base_region'] ?? null,
            'granted_scopes' => $tokens['granted_scopes'] ?? [],
        ];
    }

    protected function shopRecord(array $shop): array
    {
        return [
            'shop_id' => $shop['id'],
            'shop_cipher' => $shop['cipher'] ?? null,
            'shop_name' => $shop['name'] ?? null,
            'region' => $shop['region'] ?? null,
            'seller_type' => $shop['seller_type'] ?? null,
        ];
    }

    protected function indexShop(string $shopId, ?string $merchantRef): void
    {
        $all = Cache::get('tiktok.shops.index', []);
        $all[] = $shopId;
        Cache::forever('tiktok.shops.index', array_values(array_unique($all)));

        if ($merchantRef !== null && $merchantRef !== '') {
            $key = "tiktok.shops.merchant.{$merchantRef}";
            $ids = Cache::get($key, []);
            $ids[] = $shopId;
            Cache::forever($key, array_values(array_unique($ids)));
        }
    }

    protected function shopKey(string $shopId): string
    {
        return "tiktok.shop.{$shopId}";
    }
}
