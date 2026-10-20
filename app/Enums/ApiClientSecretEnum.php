<?php

namespace App\Enums;

enum ApiClientSecretEnum: string
{

    /****** For status ******/
    case ACTIVE = 'active';
    case RETIRING = 'retiring';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';

    /****** For direction ******/
    case INBOUND = 'inbound';
    case CALLBACK = 'callback';

    public function label(): string
    {
        return match ($this) {
            /****** For status ******/
            self::ACTIVE => 'Active',
            self::RETIRING => 'Retiring',
            self::REVOKED => 'Revoked',
            self::EXPIRED => 'Expired',


            /****** For direction ******/
            self::INBOUND => 'Inbound',
            self::CALLBACK => 'Callback',
        };
    }
}
