<?php

namespace App\Http\Controllers;

use App\Services\TikTok\Resources\LogisticService;
use App\Traits\ResponseFormatter;
use Illuminate\Http\Request;

class LogisticsController extends Controller
{
    //
    use ResponseFormatter;

    public function __construct(private LogisticService $logisticService)
    {
    }


    public function getWarehouseList(Request $request)
    {
        $warehouses = $this->logisticService->fetchWarehouses($this->tiktokShop($request)['shop_id']);

        return $this->successResponse($warehouses, 'Warehouses retrieved successfully.');
    }

    public function getWarehouseShippingOptions(Request $request, $warehouseId)
    {

        $shippingOptions = $this->logisticService->fetchShippingOptions($this->tiktokShop($request)['shop_id'], $warehouseId);

        return $this->successResponse($shippingOptions, 'Shipping options retrieved successfully.');
    }

    public function getLogisticsTracking(Request $request, $orderId)
    {
        $status = $this->logisticService->fetchTracking($this->tiktokShop($request)['shop_id'], $orderId);

        return $this->successResponse($status, 'Tracking status retrieved successfully.');
    }
}
