<?php

namespace App\Services\TikTok\Resources;

use App\Services\TikTokHttpClient;

class AuthService
{
    public function __construct(protected TikTokHttpClient $client)
    {
    }

    /**
     * Build the URL the seller visits to authorize your ISV app.
     * TikTok redirects back to your registered redirect URI with ?code=xxx.
     */
    public function getAuthorizationUrl(string $state = ''): string
    {
        /****** Call this using php artisan tinker --execute="echo app(\App\Services\TikTok\Resources\AuthService::class)->getAuthorizationUrl('test123');" ******/
        $query = http_build_query(array_filter([
            'service_id' => config('tiktok.service_id'),
            'state' => $state,
        ]));

        return config('tiktok.auth_url').'?'.$query;
    }

    /**
     * Exchange the authorization code (from the OAuth callback) for tokens.
     *
     * Returns: access_token, access_token_expire_in, refresh_token,
     * refresh_token_expire_in, open_id, seller_name, seller_base_region, granted_scopes
     */
    public function getAccessToken(string $authCode): array
    {
        return $this->client->authRequest(config('tiktok.token_url'), [
            'app_key' => config('tiktok.app_key'),
            'app_secret' => config('tiktok.app_secret'),
            'auth_code' => $authCode,
            'grant_type' => 'authorized_code',
        ]);
    }

    /**
     * Refresh an access token before it expires.
     * Note: TikTok also rotates the refresh_token — persist both from the response.
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        return $this->client->authRequest(config('tiktok.refresh_url'), [
            'app_key' => config('tiktok.app_key'),
            'app_secret' => config('tiktok.app_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
    }

    /**
     * List the shops the seller authorized (signed Open API call).
     * Each shop includes the `cipher` needed by all subsequent shop-level calls.
     */
    public function getAuthorizedShops(string $accessToken): array
    {
        return $this->client->get('/authorization/202309/shops', [], $accessToken);
    }
}


/****** Testing command ******/

// php artisan tinker
// >>> $auth = app(\App\Services\TikTok\Resources\AuthService::class);
// >>> $tokens = $auth->getAccessToken('ROW_8m0NzwAAAABKwMJwbmP98QwtqMIEZ5cNSyXfI4DwgCAS027nBtk1fIwPDfEgRfYBcitNYVCYblXM84XYmbqUX0MqWvZqtrr_H6KY321D7p8Dkkp0CGHhI6lAfIeUtuMVA4VnjZGwmMbY6gEamfuoFWSt1TawtSTLBsdwIoCjFm1iwW9K6gcq9w');
// >>> $shops = $auth->getAuthorizedShops($tokens['access_token']);