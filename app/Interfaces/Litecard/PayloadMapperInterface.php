<?php

namespace App\Interfaces\Litecard;

use App\Models\IncomingEvent;

interface PayloadMapperInterface
{
    public function toPayload(IncomingEvent $event): array;
}
