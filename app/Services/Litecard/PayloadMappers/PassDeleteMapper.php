<?php

namespace App\Services\Litecard\PayloadMappers;

use App\Enums\EventOperation;
use App\Interfaces\Litecard\PayloadMapperInterface;
use App\Models\IncomingEvent;

class PassDeleteMapper implements PayloadMapperInterface
{
    public function operation(): string
    {
        return EventOperation::USER_DEACTIVATED->operation(); // 'pass-delete'
    }

    public function toPayload(IncomingEvent $event): array
    {
        return [
            'external_id' => $event->payload['member_id'],
        ];
    }
}