<?php

namespace App\Services\Litecard\PayloadMappers;

use App\Enums\EventOperation;
use App\Interfaces\Litecard\PayloadMapperInterface;
use App\Models\IncomingEvent;

class PassCreateMapper implements PayloadMapperInterface
{
    public function operation(): string
    {
        return EventOperation::MEMBER_REGISTER->operation(); // 'pass-create'
    }

    public function endpoint(): string
    {
        return EventOperation::MEMBER_REGISTER->endpoint();
    }

    public function toPayload(IncomingEvent $event): array
    {
        $p = $event->payload;

        return [
            'templateId'  => $p['litecard']['template_id'],
            'externalId'  => (string) $p['user']['id'],
            'cardPayload' => [
                'email'       => $p['user']['email'],
                'firstName'   => $p['user']['first_name'],
                'lastName'    => $p['user']['last_name'],
                'pointsKey' => $p['user']['points'],
                "memberId" => $p['user']['membership_id'],
            ],
        ];
    }
}