<?php

namespace App\Policies;

use App\Enums\ErrorCode;

/**
 * Retry policy for incomming event deliveries.
 *
 * Encapsulates three decisions the orchestrator needs after each attempt:
 *  1. Is this outcome retryable?        — isRetryable()
 *  2. Should we retry right now?          — shouldRetry()
 *  3. How long to wait before the next?   — getDelaySeconds()
 *
 * Default schedule:
 *   Attempt 1 → immediate     Attempt 5 → 1 hour
 *   Attempt 2 → 1 minute      Attempt 6 → 4 hours
 *   Attempt 3 → 5 minutes     Attempt 7 → 12 hours
 *   Attempt 4 → 15 minutes    Attempt 8 → 24 hours
 *
 * Jitter: ±15% of base delay
 */
class RetryPolicy
{
    /** Base delay in seconds per attempt number. */
    protected const SCHEDULE = [
        1 => 0,
        2 => 60,
        3 => 300,
        4 => 900,
        5 => 3600,
        6 => 14400,
        7 => 43200,
        8 => 86400,
    ];

    /** Network-level error codes that warrant a retry. */
    protected const RETRYABLE_ERROR_CODES = [
        'dns_resolution_failed',
        'connection_refused',
        'connection_timeout',
        'read_timeout',
        'tls_handshake_failed',
        'unexpected_delivery_exception',
    ];

    /** HTTP status codes that warrant a retry. */
    protected const RETRYABLE_HTTP_STATUSES = [
        408,
        425,
        429,
    ];

    /**
     * Is this failure outcome retryable?
     *
     * Success (2xx) is NOT retryable — it's already delivered.
     * Non-retryable 4xx: 400, 401, 403, 404, 405, 410, 413, 415, 422.
     */
    public function isRetryable(?int $httpStatus, ?string $errorCode): bool
    {
        // Network-level errors are retryable
        if ($errorCode !== null && in_array($errorCode, self::RETRYABLE_ERROR_CODES, true)) {
            return true;
        }

        if ($httpStatus === null) {
            // No response and no recognised error code — treat as retryable
            return $errorCode !== null;
        }

        // Success is not "retryable" — already delivered
        if ($httpStatus >= 200 && $httpStatus < 300) {
            return false;
        }

        /****** Explicitly retryable HTTP statuses (HTTP 408, 425, 429) ******/
        if (in_array($httpStatus, self::RETRYABLE_HTTP_STATUSES, true)) {
            return true;
        }

        /****** 5xx server errors are retryable (HTTP 500 to 599) ******/
        if ($httpStatus >= 500 && $httpStatus < 600) {
            return true;
        }

        // All other 4xx are non-retryable
        return false;
    }

    /**
     * Should we schedule another attempt?
     *
     * Combines attempt-count ceiling with outcome retryability.
     */
    public function shouldRetry(int $attemptCount, int $maxAttempts, ?int $httpStatus, ?string $errorCode): bool
    {
        if ($attemptCount >= $maxAttempts) {
            return false;
        }

        return $this->isRetryable($httpStatus, $errorCode);
    }

    /**
     * Seconds to wait before the next attempt (base delay + jitter).
     *
     * @param  int  $attemptNumber  The attempt that JUST completed (1-based).
     * @return int  Delay in seconds (≥ 0).
     */
    public function getDelaySeconds(int $attemptNumber): int
    {
        $nextAttempt = $attemptNumber + 1;
        $baseDelay = self::SCHEDULE[$nextAttempt] ?? 86400;

        // Jitter: ±15% of base delay
        if ($baseDelay > 0) {
            $jitter     = (int) ($baseDelay * 0.15);
            $baseDelay += random_int(-$jitter, $jitter);
        }

        return max(0, $baseDelay);
    }

    /**
     * Map an HTTP status code to its corresponding ErrorCode value.
     */
    public function httpStatusToErrorCode(int $status): string
    {
        return match (true) {
            $status === 400    => ErrorCode::HTTP_400->value,
            $status === 401    => ErrorCode::HTTP_401->value,
            $status === 403    => ErrorCode::HTTP_403->value,
            $status === 404    => ErrorCode::HTTP_404->value,
            $status === 408    => ErrorCode::HTTP_408->value,
            $status === 410    => ErrorCode::HTTP_410->value,
            $status === 413    => ErrorCode::HTTP_413->value,
            $status === 422    => ErrorCode::HTTP_422->value,
            $status === 425    => ErrorCode::HTTP_425->value,
            $status === 429    => ErrorCode::HTTP_429->value,
            $status === 500    => ErrorCode::HTTP_500->value,
            $status === 502    => ErrorCode::HTTP_502->value,
            $status === 503    => ErrorCode::HTTP_503->value,
            $status === 504    => ErrorCode::HTTP_504->value,
            $status === 505    => ErrorCode::HTTP_505->value,
            $status >= 500     => ErrorCode::HTTP_500->value,
            default            => ErrorCode::UNEXPECTED_DELIVERY_EXCEPTION->value,
        };
    }
}
