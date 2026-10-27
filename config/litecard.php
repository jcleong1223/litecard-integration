<?php

return [
    'api_base_url'  => env('LITECARD_API_BASE_URL', 'https://bff-api.demo.litecard.io'),
    'max_response_excerpt_bytes' => env('LITECARD_MAX_RESPONSE_EXCERPT_BYTES', 8192),
    'timeout'       => env('LITECARD_TIMEOUT', 30),
];
