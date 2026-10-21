<?php

namespace App\Http\Controllers;

use App\Services\TikTok\Resources\AnalyticService;
use App\Traits\ResponseFormatter;
use Illuminate\Http\Request;

class AnalyticController extends Controller
{
    //
    use ResponseFormatter;

    public function __construct(protected AnalyticService $analyticService)
    {
    }

    public function getBestsellingProducts(Request $request)
    {

        $validated = $request->validate([
            'currency' => 'nullable|string',
            'category_id' => 'nullable|string|max:50',
            'time_slot' => 'required|string|in:1D,7D,30D',
            'date' => 'required|date',
        ]);

        $query = array_filter([
            'currency' => $validated['currency'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'time_slot' => $validated['time_slot'] ?? '1D',
            'date' => $validated['date']
        ], fn($value) => $value !== null);

        $products = $this->analyticService->fetchBestSellingProducts($this->tiktokShop($request)['shop_id'], $query);

        return $this->successResponse($products, 'Bestselling products retrieved successfully');

    }
}
