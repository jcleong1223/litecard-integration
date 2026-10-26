<?php

namespace App\Interfaces\Litecard;

use App\Models\IncomingEvent;

interface PayloadMapperInterface
{
    public function operation(): string;

    public function endpoint(): string;

    public function toPayload(IncomingEvent $event): array;
}
