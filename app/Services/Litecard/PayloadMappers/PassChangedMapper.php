<?php

namespace App\Services\Litecard\PayloadMappers;

use App\Enums\EventOperation;
use App\Interfaces\Litecard\PayloadMapperInterface;
use App\Models\IncomingEvent;

class PassChangedMapper implements PayloadMapperInterface
{
    public function toPayload(IncomingEvent $event): array
    {
        $p = $event->payload;
        $cardPayload = [
            'pointsKey' => ($points = data_get($p, 'user.points')) !== null
                ? (int) $points
                : null,
            'email'     => data_get($p, 'user.email'),
            'firstName' => data_get($p, 'user.first_name'),
            'lastName'  => data_get($p, 'user.last_name'),
            'memberId'  => data_get($p, 'user.membership_id'),
        ];

        $cardPayload = array_filter(
            $cardPayload,
            fn($value) => $value !== null && $value !== ''
        );

        return [
            'cardId' => $p['litecard']['card_id'],
            'cardPayload' => $cardPayload,
        ];
    }
}
