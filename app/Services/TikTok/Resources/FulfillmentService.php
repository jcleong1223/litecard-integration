<?php

namespace App\Services\TikTok\Resources;

use App\Services\TikTok\TokenManager;
use App\Services\TikTokHttpClient;
use Carbon\Carbon;

class FulfillmentService
{
    /**
     * Order API version.
     */
    public const VERSION = '202309';

    public function __construct(
        protected TikTokHttpClient $client,
        protected TokenManager $tokenManager,
    ) {}


    public function searchPackages(string $shopId, array $query = [], array $body = []): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        $query['page_size'] ??= 20;

        return $this->client->post(
            '/fulfillment/' . self::VERSION . '/packages/search',
            $query,
            $body,
            $token,
            $shop['shop_cipher'],
        );
    }

    /**
     * Get the full detail of one order.
     */
    public function fetchPackageDetail(string $shopId, string $packageId): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/fulfillment/' . self::VERSION . '/packages/' . $packageId,
            [],
            $token,
            $shop['shop_cipher'],
        );
    }

    public function fetchTrackingStatus(string $shopId, string $orderId): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/fulfillment/' . self::VERSION . '/orders/' . $orderId . '/tracking',
            [],
            $token,
            $shop['shop_cipher'],
        );
    }

    public function fetchPackageShippingDocument(string $shopId, string $packageId, array $query): array
    {

        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/fulfillment/' . self::VERSION . '/packages/' . $packageId . '/shipping_documents',
            $query,
            $token,
            $shop['shop_cipher'],
        );
    }

}
