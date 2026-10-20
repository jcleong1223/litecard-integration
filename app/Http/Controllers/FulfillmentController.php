<?php

namespace App\Http\Controllers;

use App\Traits\ResponseBuilder;
use Illuminate\Http\Request;
use App\Services\TikTok\Resources\FulfillmentService;

class FulfillmentController extends Controller
{
    //

    use ResponseBuilder;

    public function __construct(
        protected FulfillmentService $fulfillmentService)
    {

    }


    public function getPackages(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
            'page_token' => 'nullable|string',
            'sort_order' => 'nullable|string',
            'sort_field' => 'nullable|string',
        ]);

        $body = array_filter([
            'packages_status' => $validated['status'] ?? null,
        ], fn($value) => $value !== null);

        $query = array_filter([
            'page_size' => $validated['limit'] ?? null,
            'page_token' => $validated['page_token'] ?? null,
            'sort_order' => $validated['sort_order'] ?? null,
            'sort_field' => $validated['sort_field'] ?? null,
        ], fn($value) => $value !== null);

        $packages = $this->fulfillmentService->searchPackages($this->tiktokShop($request)['shop_id'], $query, $body);

        return $this->successResponse($packages, 'Packages retrieved successfully');
    }

    public function getPackage(Request $request, $packageId)
    {
        $package = $this->fulfillmentService->fetchPackageDetail($this->tiktokShop($request)['shop_id'], $packageId);

        return $this->successResponse($package, 'Package retrieved successfully');
    }

    public function getTrackingStatus(Request $request, $orderId)
    {
        $trackingStatus = $this->fulfillmentService->fetchTrackingStatus($this->tiktokShop($request)['shop_id'], $orderId);

        return $this->successResponse($trackingStatus, 'Tracking status retrieved successfully');
    }

    public function getPackageShippingDocument(Request $request, $packageId)
    {
        $validated = $request->validate([
            'document_type' => 'required|string|in:SHIPPING_LABEL,PACKING_SLIP,SHIPPING_LABEL_AND_PACKING_SLIP,SHIPPING_LABEL_PICTURE,HAZMAT_LABEL,INVOICE_LABEL',
            'document_page_size' => 'nullable|string|in:A6,A5,A4',
        ]);

        $query = array_filter([
            'document_size' => $validated['document_page_size'] ?? null,
            'document_type' => $validated['document_type'] ?? null
        ], fn($value) => $value !== null);

        $document = $this->fulfillmentService->fetchPackageShippingDocument($this->tiktokShop($request)['shop_id'], $packageId, $query);

        return $this->successResponse($document, 'Package shipping document retrieved successfully');
    }
}
