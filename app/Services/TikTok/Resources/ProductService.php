<?php

namespace App\Services\TikTok\Resources;

use App\Services\TikTok\TokenManager;
use App\Services\TikTokHttpClient;

class ProductService
{
    /**
     * Product API versions — they differ per endpoint:
     * search is 202502, product detail is still 202309.
     */
    public const VERSION = '202502';

    public const DETAIL_VERSION = '202309';

    public const VERSION_202405 = '202405';

    public const VERSION_202411 = '202411';

    public function __construct(
        protected TikTokHttpClient $client,
        protected TokenManager $tokenManager,
    ) {
    }

    public function fetchCategories(string $shopId, array $query = []): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/product/'.self::DETAIL_VERSION.'/categories',
            $query,
            $token,
            $shop['shop_cipher'],
        );
    }

    /**
     * Search products of a shop. Empty body returns all products.
     * Pagination via page_size/page_token follows TikTok's cursor style.
     */
    public function searchProducts(string $shopId, array $query = [], array $body = []): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        // page_size is required by TikTok and lives in the query string, not the body
        $query['page_size'] ??= 20;

        return $this->client->post(
            '/product/'.self::VERSION.'/products/search',
            $query,
            $body,
            $token,
            $shop['shop_cipher'],
        );
    }

    /**
     * Get a single product's full detail.
     */
    public function getProduct(string $shopId, string $productId, array $query = []): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/product/'.self::DETAIL_VERSION.'/products/'.$productId,
            $query,
            $token,
            $shop['shop_cipher'],
        );
    }

    public function productDiagnoseAndOptimize(string $shopId, array $query = [], array $body = [])
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->post(
            '/product/'.self::VERSION_202411. '/products/diagnose_optimize',
            $query,
            $body,
            $token,
            $shop['shop_cipher'],
        );
    }

    public function fetchSuggestedProductSeoWords(string $shopId, array $query): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/product/'.self::VERSION_202405.'/products/seo_words',
            $query,
            $token,
            $shop['shop_cipher'],
        );
    }

    public function fetchSuggestedProductTitleDescription(string $shopId, array $query): array
    {
        $shop = $this->tokenManager->shop($shopId)
            ?? throw new \InvalidArgumentException("Shop {$shopId} is not connected");

        $token = $this->tokenManager->getAccessTokenForShop($shopId);

        return $this->client->get(
            '/product/'.self::VERSION_202405.'/products/suggestions',
            $query,
            $token,
            $shop['shop_cipher'],
        );
    }

}


// testing command: php artisan tinker --execute="\$svc = app(\App\Services\TikTok\Resources\ProductService::class); \$res = \$svc->searchProducts('7494805353547597044'); echo 'total_count=' . var_export(\$res['total_count'] ?? null, true) . PHP_EOL; echo json_encode(\$res['products'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;"
