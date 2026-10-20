<?php

namespace App\Services\TikTok\Resources;

use App\Services\TikTok\TokenManager;
use App\Services\TikTokHttpClient;
use Carbon\Carbon;

class AnalyticService
{
    /**
     * Order API version.
     */
    public const VERSION_202511 = '202511';


    public function __construct(
        protected TikTokHttpClient $client,
        protected TokenManager $tokenManager,
    ) {}

    public function fetchBestSellingProducts(string $shopId, array $query): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/analytics/' . self::VERSION_202511 . '/products/bestselling',
            $query,
            $token,
            $shop['shop_cipher'],
        );
    }

}
