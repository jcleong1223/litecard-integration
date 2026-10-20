<?php

namespace App\Services\TikTok\Resources;

use App\Services\TikTok\TokenManager;
use App\Services\TikTokHttpClient;

class OrderService
{
    /**
     * Order API version.
     */
    public const VERSION = '202309';

    public function __construct(
        protected TikTokHttpClient $client,
        protected TokenManager $tokenManager,
    ) {
    }

    /**
     * Search orders of a shop.
     *
     * Body accepts TikTok's filter fields (create_time_ge/lt, update_time_ge/lt,
     * status, sort_field, sort_order, ...). Pagination (page_size/page_token)
     * lives in the query string.
     */
    public function searchOrders(string $shopId, array $query = [], array $body = []): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        $query['page_size'] ??= 20;

        return $this->client->post(
            '/order/'.self::VERSION.'/orders/search',
            $query,
            $body,
            $token,
            $shop['shop_cipher'],
        );
    }

    /**
     * Get the full detail of one order.
     */
    public function getOrderDetail(string $shopId, string $orderId): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/order/'.self::VERSION.'/orders',
            ['ids' => $orderId],
            $token,
            $shop['shop_cipher'],
        );
    }

    /**
     * Orders that were created or updated within a time window — this is how
     * "status updates" are pulled: any order whose status (payment, shipping,
     * cancellation, ...) changed falls into the update_time window.
     *
     * Poll this repeatedly with a moving window, e.g. the last 15 minutes.
     */
    public function getUpdatedOrders(
        string $shopId,
        int $updateTimeGe,
        ?int $updateTimeLt = null,
        int $pageSize = 50,
        ?string $pageToken = null,
    ): array {
        $body = [
            'update_time_ge' => $updateTimeGe,
            'update_time_lt' => $updateTimeLt ?? time(),
            'sort_field' => 'UPDATE_TIME',
            'sort_order' => 'ASC',
        ];

        $query = array_filter([
            'page_size' => $pageSize,
            'page_token' => $pageToken,
        ], fn ($value) => $value !== null);

        return $this->searchOrders($shopId, $body, $query);
    }

    /**
     * Orders in a specific status, e.g. AWAITING_SHIPMENT, SHIPPED, COMPLETED.
     */
    public function getOrdersByStatus(string $shopId, string $status, int $pageSize = 50, ?string $pageToken = null): array
    {
        $query = array_filter([
            'page_size' => $pageSize,
            'page_token' => $pageToken,
        ], fn ($value) => $value !== null);

        return $this->searchOrders($shopId, ['status' => $status], $query);
    }
}
