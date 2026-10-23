<?php

namespace App\Actions;

use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Models\InternalApiClient;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Models\WebhookEndpointSnapshot;
use App\Domain\Webhooks\Models\WebhookEvent;
use App\Enums\EventOperation;
use App\Jobs\DeliverLitecardEventJob;
use App\Models\ApiClient;
use App\Models\IncomingEvent;
use App\Models\IncomingEventDelivery;
use App\Models\LitecardCredential;
use App\Services\LitecardHttpClient;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AcceptLitecardConfigEvent
{
    public function __construct(protected LitecardHttpClient $client,)
    {

    }

    public function handle(array $data): array
    {
        try {
            $clientId = request()->header('X-SGDPLC-Client-Id');
            $clientSource = request()->header('X-SGDPLC-Source');
            $client = ApiClient::active()->with('inboundSecret')->where('client_id', $clientId)->where('name', $clientSource)->first();

            if (!$client) {
                throw new InvalidArgumentException(
                    "Invalid api client: {$clientId} ({$clientSource})"
                );
            }

            $credential = LitecardCredential::firstOrNew(['api_client_id' => $client->id]);
            $config = $data['payload']['config'];
            $fullUrl = config('litecard.api_base_url') . '/api/v1/token';
            $token = $this->client->authRequest($fullUrl, [
                'username' => $config['username'],
                'password' => $config['password'],
            ]);

            $credential->fill([
                'username_encrypted'        => Crypt::encryptString($config['username']),
                'password_encrypted'        => Crypt::encryptString($config['password']),
                'access_token_encrypted'    => Crypt::encryptString($token['access_token']),
                'access_token_expires_at'   => now()->addSeconds($token['expires_in']),
                'business_id'               => $config['business_id'] ?? null,
                'status'                    => LitecardCredential::STATUS_VERIFIED,
                'last_verified_at'          => now(),
                'last_auth_error'           => null,
            ])->save();

            return ['valid' => true, 'status' => LitecardCredential::STATUS_VERIFIED];
        } catch (QueryException $e) {

            $credential->fill([
                'status'              => LitecardCredential::STATUS_INVALID,
                'last_auth_failed_at' => now(),
                'last_auth_error'     => substr($e->getMessage(), 0, 255),
            ])->save();

            return ['valid' => false, 'status' => LitecardCredential::STATUS_INVALID, 'message' => $e->getMessage()];
        }
    }
}
