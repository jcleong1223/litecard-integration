<?php

namespace App\Http\Controllers;

use App\Services\TikTok\Resources\OrderService;
use App\Traits\ResponseBuilder;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    //
    use ResponseBuilder;

    public function __construct(
        protected OrderService $orderService,
    ) {}

    public function getOrders(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|string',
            'shipping_type' => 'nullable|string',
            'buyer_id' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
            'page_token' => 'nullable|string',
            'sort_order' => 'nullable|string',
            'sort_field' => 'nullable|string',
        ]);

        $body = array_filter([
            'buyer_user_id' => $validated['buyer_id'] ?? null,
            'order_status' => $validated['status'] ?? null,
            'shipping_type' => $validated['shipping_type'] ?? null,
        ], fn($value) => $value !== null);

        $query = array_filter([
            'page_size' => $validated['limit'] ?? null,
            'page_token' => $validated['page_token'] ?? null,
            'sort_order' => $validated['sort_order'] ?? null,
            'sort_field' => $validated['sort_field'] ?? null,
        ], fn($value) => $value !== null);

        $orders = $this->orderService->searchOrders($this->tiktokShop($request)['shop_id'], $query, $body);

        return $this->successResponse($orders, 'Orders retrieved successfully');
    }

    public function getOrder(Request $request, string $orderId)
    {
        $order = $this->orderService->getOrderDetail($this->tiktokShop($request)['shop_id'], $orderId);

        return $this->successResponse($order, 'Order retrieved successfully');
    }

}