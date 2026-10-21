<?php

return [
    'connection_timeout' => env('SOURCE_CONNECT_TIMEOUT', 3),
    'default_timeout' => env('SOURCE_DEFAULT_TIMEOUT', 10),
    'max_timeout' => env('SOURCE_MAX_TIMEOUT', 30),
    'default_max_attempts' => env('SOURCE_DEFAULT_MAX_ATTEMPTS', 8),
    'max_payload_bytes' => env('SOURCE_MAX_PAYLOAD_BYTES', 1048576),
    'max_response_excerpt_bytes' => env('SOURCE_RESPONSE_EXCERPT_BYTES', 8192),
    'allowed_clock_skew_seconds' => env('SOURCE_ALLOWED_CLOCK_SKEW_SECONDS', 300),
    'require_https' => env('SOURCE_REQUIRE_HTTPS', true),
    'allowed_ports' => array_values(array_filter(array_map('intval', explode(',', (string) env('SOURCE_ALLOWED_PORTS', '443'))))),
    'allow_redirects' => env('SOURCE_ALLOW_REDIRECTS', false),
    'warning_attempt' => env('SOURCE_WARNING_ATTEMPT', 3),
    'degraded_failure_count' => env('SOURCE_DEGRADED_FAILURE_COUNT', 10),
    'down_failure_count' => env('SOURCE_DOWN_FAILURE_COUNT', 20)
];
