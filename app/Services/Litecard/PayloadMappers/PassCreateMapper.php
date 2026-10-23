<?php

namespace App\Services\Litecard\PayloadMappers;

use App\Enums\EventOperation;
use App\Interfaces\Litecard\PayloadMapperInterface;
use App\Models\IncomingEvent;

class PassCreateMapper implements PayloadMapperInterface
{
    public function operation(): string
    {
        return EventOperation::USER_REGISTER->operation(); // 'pass-create'
    }

    public function toPayload(IncomingEvent $event): array
    {
        $p = $event->payload;

        return [
            'templateId'  => $p['template_id'],
            'cardPayload' => [
                'email'       => $p['email'],
                'phone'       => $p['phone'],
                'exampleProperty' => 'Member card for your membership program'
            ],
            'businessName' => 'Hello Testing Xia'
        ];
    }
}