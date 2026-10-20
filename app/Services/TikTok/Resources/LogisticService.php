<?php

namespace App\Services\TikTok\Resources;

use App\Services\TikTok\TokenManager;
use App\Services\TikTokHttpClient;
use Carbon\Carbon;

class LogisticService
{
    /**
     * Order API version.
     */
    public const VERSION_202309 = '202309';

    public const VERSION_202604 = '202604';

    public function __construct(
        protected TikTokHttpClient $client,
        protected TokenManager $tokenManager,
    ) {}

    public function fetchWarehouses(string $shopId, array $query = []): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/logistics/'.self::VERSION_202309. '/warehouses',
            $query,
            $token,
            $shop['shop_cipher'],
        );
    }

    public function fetchShippingOptions(string $shopId, string $warehouseId): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/logistics/'.self::VERSION_202309. '/warehouses/'. $warehouseId .'/delivery_options',
            [],
            $token,
            $shop['shop_cipher'],
        );
    }

    public function fetchTracking(string $shopId, string $orderId): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/logistics/'.self::VERSION_202604. '/orders/'.$orderId.'/tracking',
            ['order_id' => $orderId],
            $token,
            $shop['shop_cipher'],
        );
    }
}