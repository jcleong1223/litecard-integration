<?php

namespace App\Domain\Webhooks\Actions;

use App\Domain\Webhooks\Contracts\WebhookSigner;
use App\Domain\Webhooks\Contracts\WebhookTransport;
use App\Domain\Webhooks\Enums\WebhookAlertType;
use App\Domain\Webhooks\Jobs\DeliverWebhookJob;
use App\Domain\Webhooks\Jobs\SendWebhookAlertJob;
use App\Domain\Webhooks\Models\WebhookDelivery;
use App\Domain\Webhooks\Models\WebhookDeliveryAttempt;
use App\Domain\Webhooks\Models\WebhookEndpointSnapshot;
use App\Domain\Webhooks\Policies\WebhookRetryPolicy;
use App\Domain\Webhooks\Policies\WebhookAlertPolicy;
use App\Domain\Webhooks\Services\EndpointHealthService;
use App\Domain\Webhooks\Services\WebhookPayloadBuilder;
use App\Models\IncomingEventDelivery;
use Illuminate\Support\Facades\Log;


class VerifyLitecardCreential
{
    public function handle()
    {
    }
}