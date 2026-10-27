<?php

namespace App\Services\Litecard\PayloadMappers;

use App\Enums\EventOperation;
use App\Interfaces\Litecard\PayloadMapperInterface;
use App\Models\IncomingEvent;

class PassStatusMapper implements PayloadMapperInterface
{
    public function toPayload(IncomingEvent $event): array
    {
        $p = $event->payload;

        return [
            'cardId' => $p['litecard']['card_id'],
            'status' => $p['user']['status'],
        ];
    }
}
