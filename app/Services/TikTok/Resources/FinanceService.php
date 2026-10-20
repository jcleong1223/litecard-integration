<?php

namespace App\Services\TikTok\Resources;

use App\Services\TikTok\TokenManager;
use App\Services\TikTokHttpClient;
use Carbon\Carbon;

class FinanceService
{
    /**
     * Order API version.
     */
    public const VERSION_202309 = '202309';

    public const VERSION_202501 = '202501';

    public const VERSION_202507 = '202507';

    public const VERSION_202605 = '202605';

    public function __construct(
        protected TikTokHttpClient $client,
        protected TokenManager $tokenManager,
    ) {}


    public function fetchStatements(string $shopId, array $query): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        $query['page_size'] ??= 20;

        return $this->client->get(
            '/finance/' . self::VERSION_202309 . '/statements',
            $query,
            $token,
            $shop['shop_cipher'],
        );

    }

    public function fetchTransactionsByOrder(string $shopId, string $orderId): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/finance/' . self::VERSION_202501 . '/orders/' . $orderId . '/statement_transactions',
            [],
            $token,
            $shop['shop_cipher'],
        );
    }

    public function fetchUnsettledTransactions(string $shopId, array $query): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        $query['page_size'] ??= 20;

        return $this->client->get(
            '/finance/' . self::VERSION_202507 . '/orders/unsettled',
            $query,
            $token,
            $shop['shop_cipher'],
        );
    }

    public function fetchPayments(string $shopId, array $query): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        $query['page_size'] ??= 20;

        return $this->client->get(
            '/finance/' . self::VERSION_202605 . '/payments',
            $query,
            $token,
            $shop['shop_cipher'],
        );
    }
}
