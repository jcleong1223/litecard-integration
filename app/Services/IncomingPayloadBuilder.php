<?php

namespace App\Domain\Webhooks\Services;

use App\Enums\EventOperation;
use App\Models\IncomingEvent;
use App\Services\Litecard\PayloadMapperFactory;

class IncomingPayloadBuilder
{
    public function __construct(
        private readonly PayloadMapperFactory $mapperFactory,
    ) {}

    /**
     * Build the §11.1 envelope for the given event.
     *
     * @return array  JSON-serializable envelope.
     */
    public function build(IncomingEvent $event): array
    {
        $operation = EventOperation::from($event->event_type);
        $mapper    = $this->mapperFactory->make($operation);

        return [
            'id'          => $event->id,
            'type'        => $event->event_type,
            'api_version' => $event->api_version ?? 'v1',
            'created_at'  => $event->occurred_at->toIso8601String(),
            'tenant'      => [
                'id'   => $event->tenant_id,
                'code' => $event->tenant_code,
            ],
            'outlet'    => [
                'id'   => $event->merchant_id,
                'name' => $event->merchant_name ?? '',
            ],
            'operation'   => $mapper->operation(),
            'data'        => $mapper->toPayload($event),
        ];
    }
}
