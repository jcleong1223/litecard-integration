<?php

namespace App\Enums;

enum EventType: string
{
    /****** Member events ******/
    case MEMBER_REGISTERED = 'member.registered';
    case MEMBER_UPDATED = 'member.updated';
    case MEMBER_DELETED = 'member.deleted';
    case MEMBER_DEACTIVATED = 'member.deactivated';
    case MEMBER_ACTIVATED = 'member.activated';
    case MEMBER_EXPIRED = 'member.expired';
    case POINT_CHANGED = 'point.changed';
    case VOUCHER_ISSUED = 'voucher.issued';
    case VOUCHER_REDEEMED = 'voucher.redeemed';
    case VOUCHER_EXPIRED = 'voucher.expired';

    case CARD_VIEW = 'card.view';

    /****** Litecard config ******/
    case LITECARD_CONFIG = 'litecard.config';

    public function label(): string
    {
        return match ($this) {
            self::MEMBER_REGISTERED => 'Member registered',
            self::MEMBER_UPDATED => 'Member updated',
            self::MEMBER_DELETED => 'Member deleted',
            self::MEMBER_DEACTIVATED => 'Member deactivated',
            self::MEMBER_ACTIVATED => 'Member activated',
            self::MEMBER_EXPIRED => 'Member expired',
            self::POINT_CHANGED => 'Point changed',
            self::VOUCHER_ISSUED => 'Voucher issued',
            self::VOUCHER_REDEEMED => 'Voucher redeemed',
            self::VOUCHER_EXPIRED => 'Voucher expired',
            self::CARD_VIEW => 'View card',
            self::LITECARD_CONFIG => 'Litecard config',
        };
    }

    public function operation(): ?EventOperation
    {
        return match ($this) {
            self::MEMBER_REGISTERED   => EventOperation::PASS_CREATE,

            self::MEMBER_UPDATED,
            self::POINT_CHANGED      => EventOperation::PASS_UPDATE,

            self::MEMBER_DELETED     => EventOperation::PASSES_DELETE,

            self::CARD_VIEW          => EventOperation::CARD_VIEW,

            self::MEMBER_ACTIVATED,
            self::MEMBER_EXPIRED,
            self::MEMBER_DEACTIVATED,
            self::VOUCHER_REDEEMED     => EventOperation::PASS_STATUS,

            default                  => null,
        };
    }
}
