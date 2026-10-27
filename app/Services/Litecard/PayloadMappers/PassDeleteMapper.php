<?php

namespace App\Services\Litecard\PayloadMappers;

use App\Enums\EventOperation;
use App\Interfaces\Litecard\PayloadMapperInterface;
use App\Models\IncomingEvent;

class PassDeleteMapper implements PayloadMapperInterface
{
    public function toPayload(IncomingEvent $event): array
    {
        $p = $event->payload;

        return [
            'email' => $p['user']['email'],
            'databaseDelete' => true
        ];
    }
}