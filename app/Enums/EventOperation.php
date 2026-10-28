<?php

namespace App\Enums;

enum EventOperation: string
{
    case PASS_CREATE  = 'pass-create';
    case PASS_STATUS  = 'pass-status';
    case PASSES_DELETE  = 'passes-delete';
    case PASS_UPDATE  = 'pass-update';
    case CARD_VIEW    = 'card-view';

    public function endpoint(): string
    {
        return match ($this) {
            self::PASS_CREATE  => '/api/v1/card',
            self::PASS_UPDATE => '/api/v1/card',
            self::CARD_VIEW    => '/api/v1/card',
            self::PASSES_DELETE  => '/api/v1/card-owner/delete',
            self::PASS_STATUS  => '/api/v1/card/status',
        };
    }

    public function method(): string
    {
        return match ($this) {
            self::PASS_CREATE  => 'POST',
            self::PASS_UPDATE => 'PATCH',
            self::CARD_VIEW    => 'GET',
            self::PASSES_DELETE  => 'DELETE',
            self::PASS_STATUS  => 'POST',
        };
    }
}
