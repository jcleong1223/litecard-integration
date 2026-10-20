<?php

namespace App\Enums;

enum ErrorCode: string
{
    /*** Internal API / ingestion error codes (spec §9) ***/
    case VALIDATION_ERROR = 'validation_error';
    case INVALID_SIGNATURE = 'invalid_signature';
    case INVALID_CLIENT = 'invalid_client';
    case REQUEST_TIMESTAMP_EXPIRED = 'request_timestamp_expired';
    case IP_NOT_ALLOWED = 'ip_not_allowed';
    case REPLAY_DETECTED = 'replay_detected';
    case INVALID_PAYLOAD_SIZE = 'invalid_payload_size';
    case RESOURCE_NOT_FOUND = 'resource_not_found';

    /*** Delivery error codes (spec §29) ***/
    case DNS_RESOLUTION_FAILED = 'dns_resolution_failed';
    case CONNECTION_REFUSED = 'connection_refused';
    case CONNECTION_TIMEOUT = 'connection_timeout';
    case READ_TIMEOUT = 'read_timeout';
    case TLS_HANDSHAKE_FAILED = 'tls_handshake_failed';
    case INVALID_CERTIFICATE = 'invalid_certificate';
    case HTTP_400 = 'http_400';
    case HTTP_401 = 'http_401';
    case HTTP_403 = 'http_403';
    case HTTP_404 = 'http_404';
    case HTTP_408 = 'http_408';
    case HTTP_410 = 'http_410';
    case HTTP_413 = 'http_413';
    case HTTP_422 = 'http_422';
    case HTTP_425 = 'http_425';
    case HTTP_429 = 'http_429';
    case HTTP_500 = 'http_500';
    case HTTP_502 = 'http_502';
    case HTTP_503 = 'http_503';
    case HTTP_504 = 'http_504';
    case HTTP_505 = 'http_505';
    case RESPONSE_TOO_LARGE = 'response_too_large';
    case INVALID_REDIRECT = 'invalid_redirect';
    case BLOCKED_DESTINATION = 'blocked_destination';
    case REQUEST_BUILD_FAILED = 'request_build_failed';
    case UNEXPECTED_DELIVERY_EXCEPTION = 'unexpected_delivery_exception';

    public function label(): string
    {
        return match ($this) {
            self::VALIDATION_ERROR => 'Validation Error',
            self::INVALID_SIGNATURE => 'Invalid Signature',
            self::INVALID_CLIENT => 'Invalid Client',
            self::REQUEST_TIMESTAMP_EXPIRED => 'Request Timestamp Expired',
            self::IP_NOT_ALLOWED => 'IP Not Allowed',
            self::REPLAY_DETECTED => 'Replay Detected',
            self::INVALID_PAYLOAD_SIZE => 'Invalid Payload Size',
            self::RESOURCE_NOT_FOUND => 'Resource Not Found',
            self::DNS_RESOLUTION_FAILED => 'DNS Resolution Failed',
            self::CONNECTION_REFUSED => 'Connection Refused',
            self::CONNECTION_TIMEOUT => 'Connection Timeout',
            self::READ_TIMEOUT => 'Read Timeout',
            self::TLS_HANDSHAKE_FAILED => 'TLS Handshake Failed',
            self::INVALID_CERTIFICATE => 'Invalid Certificate',
            self::HTTP_400 => 'HTTP 400',
            self::HTTP_401 => 'HTTP 401',
            self::HTTP_403 => 'HTTP 403',
            self::HTTP_404 => 'HTTP 404',
            self::HTTP_408 => 'HTTP 408',
            self::HTTP_410 => 'HTTP 410',
            self::HTTP_413 => 'HTTP 413',
            self::HTTP_422 => 'HTTP 422',
            self::HTTP_425 => 'HTTP 425',
            self::HTTP_429 => 'HTTP 429',
            self::HTTP_500 => 'HTTP 500',
            self::HTTP_502 => 'HTTP 502',
            self::HTTP_503 => 'HTTP 503',
            self::HTTP_504 => 'HTTP 504',
            self::HTTP_505 => 'HTTP 505',
            self::RESPONSE_TOO_LARGE => 'Response Too Large',
            self::INVALID_REDIRECT => 'Invalid Redirect',
            self::BLOCKED_DESTINATION => 'Blocked Destination',
            self::REQUEST_BUILD_FAILED => 'Request Build Failed',
            self::UNEXPECTED_DELIVERY_EXCEPTION => 'Unexpected Delivery Exception',
        };
    }
}
