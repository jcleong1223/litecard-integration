<?php

namespace App\Http\Middleware;

use App\Enums\ErrorCode;
use App\Models\ApiClient;
use App\Traits\ResponseFormatter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\WebhookRespFormatter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

class AuthenticateApiClient
{
    use ResponseFormatter;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $headerData = [
            'x-client-id-header'  => $request->header('X-SGDPLC-Client-Id'),
            'x-source-header'     => $request->header('X-SGDPLC-Source'),
            'x-timestamp-header'  => $request->header('X-SGDPLC-Timestamp'),
            'x-request-id-header' => $request->header('X-SGDPLC-Request-Id'),
            'x-signature-header'  => $request->header('X-SGDPLC-Signature'),
        ];

        $validator = Validator::make($headerData, [
            'x-client-id-header'  => 'required|string|max:50',
            'x-source-header'     => 'required|string|max:50',
            'x-timestamp-header'  => 'required|integer',
            'x-request-id-header' => 'required|string|max:100',
            'x-signature-header'  => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            /****** Use forbiddenResponse so that not to reveal what fields are required ******/
            return $this->forbiddenResponse('Invalid request headers.');
        }

        $clientId = $request->header('X-SGDPLC-Client-Id');
        $clientSource = $request->header('X-SGDPLC-Source');
        $requestTimestampInUnix = (int) $request->header('X-SGDPLC-Timestamp');
        $requestId = $request->header('X-SGDPLC-Request-Id');
        $requestSignature = $request->header('X-SGDPLC-Signature');

        $client = ApiClient::active()->where('client_id', $clientId)->where('name', $clientSource)->first();

        /****** Client disable checkign ******/
        if (!$client) {
            return $this->unauthorizedResponse(
                'Invalid client credentials or client is disabled.',
                ErrorCode::INVALID_CLIENT->name
            );
        }

        /****** Allowble request time range checking ******/
        $allowableSkew = config('sourcesystem.allowed_clock_skew_seconds') ?? 300;

        if (abs(time() - $requestTimestampInUnix) > (int) $allowableSkew) {
            return $this->unauthorizedResponse(
                'Request timestamp is outside the allowed range.',
                ErrorCode::REQUEST_TIMESTAMP_EXPIRED->name
            );
        }

        /****** Allowable request size checkign ******/
        $maxSizeBytes = config('sourcesystem.max_payload_bytes') ?? 1048576;
        if (strlen($request->getContent()) > $maxSizeBytes) {
            return $this->errorResponse(
                false,
                ErrorCode::INVALID_PAYLOAD_SIZE->name,
                'Request payload exceeds maximum allowed size.',
                Response::HTTP_REQUEST_ENTITY_TOO_LARGE
            );
        }

        /****** Ip address checking ******/
        if (!empty($client->allowed_ips) && !in_array($request->ip(), $client->allowed_ips, true)) {
            return $this->unauthorizedResponse(
                'Client IP address is not authorized.',
                ErrorCode::IP_NOT_ALLOWED->name
            );
        }

        // 6. HMAC Signature Check
        $rawBody = $request->getContent();

        try {
            $secret = Crypt::decryptString($client->secret_encrypted);
        } catch (\Throwable $e) {
            Log::error('Internal API client secret could not be decrypted.', [
                'client_id' => $clientId,
            ]);

            return $this->errorResponse(
                false,
                'INTERNAL_ERROR',
                'Unable to verify client credentials.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $signedInput = $requestTimestampInUnix . '.' . $rawBody;
        $expectedSignature = 'v1=' . hash_hmac('sha256', $signedInput, $secret);

        if (!hash_equals($expectedSignature, $requestSignature)) {
            return $this->unauthorizedResponse('The request signature is invalid.');
        }

        /****** Request replay protection (spec §25.8) ******/
        // $replayKey = 'sgdp:request_id:' . $clientId . ':' . $requestId;
        // if (!Cache::add($replayKey, true, now()->addSeconds($allowableSkew * 2))) {
        //     return $this->unauthorizedResponse(
        //         'This request ID has already been used.',
        //         WebhookErrorCode::REPLAY_DETECTED->name
        //     );
        // }

        $client->updateQuietly(['last_used_at' => now()]);


        return $next($request);
    }
}
