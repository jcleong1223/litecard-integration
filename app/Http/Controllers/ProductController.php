<?php

namespace App\Http\Controllers;

use App\Services\TikTok\Resources\ProductService;
use App\Traits\ResponseFormatter;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ResponseFormatter;

    public function __construct(
        protected ProductService $productService,
    ) {}

    public function getCategories(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'nullable|string|max:10',
            'keyword' => 'nullable|string|max:100',
            'include_prohibited_categories' => 'nullable|bool',
        ]);

        $query = array_filter([
            'locale' => $validated['locale'] ?? 'en-US',
            'keyword' => $validated['keyword'] ?? null,
            'include_prohibited_categories' => $validated['include_prohibited_categories'] ?? null,
        ], fn ($value) => $value !== null);

        $categories = $this->productService->fetchCategories($this->tiktokShop($request)['shop_id'], $query);

        return $this->successResponse($categories, 'Categories retrieved successfully');
    }

    public function getProducts(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'nullable|string',
            'status' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
            'page_token' => 'nullable|string',
        ]);

        // TikTok search filters belong in the request body
        $body = array_filter([
            'keyword' => $validated['keyword'] ?? null,
            'status' => $validated['status'] ?? null,
        ], fn ($value) => $value !== null);

        // Pagination lives in the query string, with TikTok's own param names
        $query = array_filter([
            'page_size' => $validated['limit'] ?? null,
            'page_token' => $validated['page_token'] ?? null,
        ], fn ($value) => $value !== null);

        $products = $this->productService->searchProducts($this->tiktokShop($request)['shop_id'], $query, $body);

        return $this->successResponse($products, 'Products retrieved successfully');
    }

    public function getProduct(Request $request, string $productId)
    {

        $product = $this->productService->getProduct($this->tiktokShop($request)['shop_id'], $productId);

        return $this->successResponse($product, 'Product retrieved successfully');
    }

    public function diagnoseAndOptimizeProduct(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|string|max:50',
            'product_id' => 'nullable|string|max:50',
            'brand_id' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:100',
        ]);

        $body = array_filter([
            'category_id' => $validated['category_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'title' => $validated['title'] ?? null,
        ], fn($value) => $value !== null);

        $diagnosedResult = $this->productService->productDiagnoseAndOptimize($this->tiktokShop($request)['shop_id'], [], $body);

        return $this->successResponse($diagnosedResult, 'Product diagnosed and optimized successfully');
    }

    public function getProductSeoWords(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => 'required|array|max:20|min:1',
            'product_ids.*' => 'string|max:50',
        ]);

        $seoWords = $this->productService->fetchSuggestedProductSeoWords($this->tiktokShop($request)['shop_id'], $validated);

        return $this->successResponse($seoWords, 'Product SEO words retrieved successfully');
    }

    public function getProductRecommendedTitleDescription(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => 'required|array|max:20|min:1',
            'product_ids.*' => 'string|max:50',
        ]);

        $seoWords = $this->productService->fetchSuggestedProductTitleDescription($this->tiktokShop($request)['shop_id'], $validated);

        return $this->successResponse($seoWords, 'Product SEO words retrieved successfully');
    }
}