<?php

namespace App\Enums;

enum EventOperation: string
{

    case USER_REGISTER = 'user.registered';
    case USER_DEACTIVATED = 'user.deactivated';

    public function operation(): string
    {
        return match ($this) {
            self::USER_REGISTER => 'pass-create',
            self::USER_DEACTIVATED => 'pass-delete',
        };
    }
}
