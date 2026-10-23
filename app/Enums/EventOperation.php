<?php

namespace App\Enums;

enum EventOperation: string
{
    case LITECARD_CONFIG = 'litecard.config';
    case USER_REGISTER = 'user.register';
    case USER_DEACTIVATED = 'user.deactivated';

    public function operation(): string
    {
        return match ($this) {
            self::LITECARD_CONFIG => 'litecard-config',
            self::USER_REGISTER => 'pass-create',
            self::USER_DEACTIVATED => 'pass-delete',
        };
    }
}
