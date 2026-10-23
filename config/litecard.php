<?php

return [
    'api_base_url'  => env('LITECARD_API_BASE_URL', 'https://bff-api.demo.litecard.io'),

    'timeout'       => env('LITECARD_TIMEOUT', 30),

    // For single-merchant testing (filled after first auth)
    'access_token'  => env('TIKTOK_ACCESS_TOKEN'),
    'refresh_token' => env('TIKTOK_REFRESH_TOKEN'),
    'shop_id'       => env('TIKTOK_SHOP_ID'),
];
