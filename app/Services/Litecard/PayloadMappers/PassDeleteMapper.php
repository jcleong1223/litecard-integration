<?php

namespace App\Services\Litecard\PayloadMappers;

use App\Enums\EventOperation;
use App\Interfaces\Litecard\PayloadMapperInterface;
use App\Models\IncomingEvent;

class PassDeleteMapper implements PayloadMapperInterface
{
    public function operation(): string
    {
        return EventOperation::MEMBER_DEACTIVATED->operation(); // 'pass-delete'
    }

    public function endpoint(): string
    {
        return EventOperation::MEMBER_DEACTIVATED->endpoint();
    }

    public function toPayload(IncomingEvent $event): array
    {
        $p = $event->payload;

        return [
            'email' => $p['user']['email'],
            'phone' => $p['user']['mobile'],
            'databaseDelete' => false
        ];
    }
}