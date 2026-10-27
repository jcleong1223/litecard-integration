<?php


namespace App\Services\Litecard;

use App\Enums\EventOperation;
use App\Interfaces\Litecard\PayloadMapperInterface;
use App\Services\Litecard\PayloadMappers\PassCreateMapper;
use App\Services\Litecard\PayloadMappers\PassDeleteMapper;
use App\Services\Litecard\PayloadMappers\PassStatusMapper;
use App\Services\Litecard\PayloadMappers\PointChangedMapper;
use InvalidArgumentException;

class PayloadMapperFactory
{
    /** @var array<string, class-string<PayloadMapperInterface>> */

    public function make(string $operation): PayloadMapperInterface
    {
        $class = match ($operation) {
            EventOperation::PASS_CREATE->value  => PassCreateMapper::class,
            EventOperation::PASS_STATUS->value  => PassStatusMapper::class,
            EventOperation::PASSES_DELETE->value  => PassDeleteMapper::class,
            EventOperation::POINT_UPDATE->value => PointChangedMapper::class,
            EventOperation::CARD_VIEW->value    => throw new InvalidArgumentException('card-view carries no request body'),
        };

        return app($class);
    }
}