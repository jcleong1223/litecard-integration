<?php

namespace App\Enums;

enum EventOperation: string
{

    case MEMBER_REGISTER = 'member.registered';
    case MEMBER_DEACTIVATED = 'member.deactivated';

    public function operation(): string
    {
        return match ($this) {
            self::MEMBER_REGISTER => 'pass-create',
            self::MEMBER_DEACTIVATED => 'pass-delete',
        };
    }

    public function endpoint(): string
    {
        return match ($this) {
            self::MEMBER_REGISTER => '/api/v1/card',
            self::MEMBER_DEACTIVATED => '/api/v1/card-owner/delete',
        };
    }
}
