<?php

namespace App\Http\Controllers;

use App\Services\TikTok\Resources\FinanceService;
use App\Traits\ResponseFormatter;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    //
    use ResponseFormatter;

    public function __construct(protected FinanceService $financeService)
    {

    }

    public function getStatements(Request $request)
    {
        $validated = $request->validate([
            'payment_status' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
            'page_token' => 'nullable|string',
            'sort_order' => 'nullable|string|in:ASC,DESC',
            'sort_field' => 'nullable|string',
        ]);

        $sortField = 'statement_time';

        $query = array_filter([
            'payment_status' => $validated['payment_status'] ?? null,
            'page_size' => $validated['limit'] ?? null,
            'page_token' => $validated['page_token'] ?? null,
            'sort_field' => $sortField,
            'sort_order' => $validated['sort_order'] ?? null,
        ], fn ($value) => $value !== null);

        $statements = $this->financeService->fetchStatements($this->tiktokShop($request)['shop_id'], $query);

        return $this->successResponse($statements, 'Finance statement retrieved successfully');
    }

    public function getTransactionsByOrder(Request $request, string $orderId)
    {
        $transactions = $this->financeService->fetchTransactionsByOrder($this->tiktokShop($request)['shop_id'], $orderId);

        return $this->successResponse($transactions, 'Finance statement retrieved successfully');
    }

    public function getUnsettledTransactions(Request $request)
    {
        $validated = $request->validate([
            'sort_order' => 'nullable|string|in:ASC,DESC',
            'limit' => 'nullable|integer|min:1|max:100',
            'page_token' => 'nullable|string',
        ]);

        $sortField = 'order_create_time';

        $query = array_filter([
            'page_size' => $validated['limit'] ?? null,
            'page_token' => $validated['page_token'] ?? null,
            'sort_field' => $sortField,
            'sort_order' => $validated['sort_order'] ?? null,
        ], fn ($value) => $value !== null);

        $unsettledTransx = $this->financeService->fetchUnsettledTransactions($this->tiktokShop($request)['shop_id'], $query);

        return $this->successResponse($unsettledTransx, 'Unsettled transactions retrieved successfully');
    }

    public function getPayments(Request $request)
    {
        $validated = $request->validate([
            'sort_order' => 'nullable|string|in:ASC,DESC',
            'limit' => 'nullable|integer|min:1|max:100',
            'page_token' => 'nullable|string',
        ]);

        $sortField = 'create_time';

        $query = array_filter([
            'page_size' => $validated['limit'] ?? null,
            'page_token' => $validated['page_token'] ?? null,
            'sort_field' => $sortField,
            'sort_order' => $validated['sort_order'] ?? null,
        ], fn ($value) => $value !== null);

        $payments = $this->financeService->fetchPayments($this->tiktokShop($request)['shop_id'], $query);

        return $this->successResponse($payments, 'Payments retrieved successfully');
    }
}
