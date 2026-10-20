<?php

namespace App\Http\Controllers;

use App\Services\TikTok\Exceptions\TikTokApiException;
use App\Services\TikTok\Resources\AuthService;
use App\Services\TikTok\TokenManager;
use App\Traits\ResponseBuilder;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ResponseBuilder;

    public function __construct(
        protected AuthService $authService,
        protected TokenManager $tokenManager,
    ) {
    }


    public function authorizeApp(Request $request)
    {
        $validated = $request->validate([
            'merchant_ref' => 'nullable|string|max:64',
        ]);

        $state = Crypt::encrypt([
            'merchant_ref' => $validated['merchant_ref'] ?? null,
            'nonce' => Str::random(16),
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        return $this->successResponse([
            'authorization_url' => $this->authService->getAuthorizationUrl($state),
            'expires_in_minutes' => 30,
        ], 'Visit the authorization URL to connect your TikTok shop');
    }

    public function getAuthorizedShops(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'state' => 'nullable|string',
        ]);

        $merchantRef = null;

        if (! empty($validated['state'])) {
            try {
                $state = Crypt::decrypt($validated['state']);
            } catch (DecryptException) {
                return $this->errorResponse(message: 'Invalid state parameter', statusCode: 403);
            }

            if (($state['expires_at'] ?? 0) < now()->timestamp) {
                return $this->errorResponse(message: 'Authorization session expired, please restart the connect flow', statusCode: 403);
            }

            $merchantRef = $state['merchant_ref'] ?? null;
        }

        try {
            $tokens = $this->authService->getAccessToken($validated['code']);
            $shops = $this->authService->getAuthorizedShops($tokens['access_token']);

        } catch (TikTokApiException $e) {
            return $this->errorResponse(
                message: 'TikTok authorization failed: '.$e->getMessage(),
                errors: [
                    'tiktok_code' => $e->getBusinessCode(),
                    'request_id' => $e->getRequestId(),
                ],
                statusCode: 502,
            );
        }

        $shopList = $shops['shops'] ?? [];

        $this->tokenManager->saveTokenSet($tokens, $shopList, $merchantRef);

        return $this->successResponse([
            'merchant_ref' => $merchantRef,
            'seller_name' => $tokens['seller_name'] ?? null,
            'access_token_preview' => Str::mask($tokens['access_token'], '*', 8),
            'access_token_expires_at' => $tokens['access_token_expire_in'] ?? null,
            'granted_scopes' => $tokens['granted_scopes'] ?? [],
            'shops' => collect($shopList)->map(fn ($shop) => [
                'shop_id' => $shop['id'],
                'name' => $shop['name'] ?? null,
                'region' => $shop['region'] ?? null,
            ])->all(),
        ], 'TikTok shop connected successfully');
    }
}
