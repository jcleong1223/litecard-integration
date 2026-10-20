<?php

return [
    'app_key'    => env('TIKTOK_APP_KEY'),
    'app_secret' => env('TIKTOK_APP_SECRET'),
    'auth_base_url' => env('TIKTOK_AUTH_BASE_URL'),
    'api_base_url'  => env('TIKTOK_API_BASE_URL', 'https://open-api.tiktokglobalshop.com'),
    'auth_url'      => env('TIKTOK_AUTH_URL', 'https://services.tiktokshop.com/open/authorize'),
    'token_url'     => 'https://auth.tiktok-shops.com/api/v2/token/get',
    'refresh_url'   => 'https://auth.tiktok-shops.com/api/v2/token/refresh',

    // ISV service id from Partner Center (used in the seller authorization link)
    'service_id'    => env('TIKTOK_SERVICE_ID'),
    'redirect_uri'  => env('TIKTOK_REDIRECT_URI'),

    'timeout'       => env('TIKTOK_TIMEOUT', 30),

    // For single-merchant testing (filled after first auth)
    'access_token'  => env('TIKTOK_ACCESS_TOKEN'),
    'refresh_token' => env('TIKTOK_REFRESH_TOKEN'),
    'shop_id'       => env('TIKTOK_SHOP_ID'),
];
