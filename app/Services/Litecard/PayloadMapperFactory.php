<?php


namespace App\Services\Litecard;

use App\Enums\EventOperation;
use App\Interfaces\Litecard\PayloadMapperInterface;
use App\Services\Litecard\PayloadMappers\PassCreateMapper;
use App\Services\Litecard\PayloadMappers\PassDeleteMapper;

use InvalidArgumentException;

class PayloadMapperFactory
{
    /** @var array<string, class-string<PayloadMapperInterface>> */
    private const MAPPERS = [
        'member.registered'    => PassCreateMapper::class,
        'member.deactivated' => PassDeleteMapper::class,
    ];

    public function make(EventOperation $operation): PayloadMapperInterface
    {
        $class = self::MAPPERS[$operation->value]
            ?? throw new InvalidArgumentException("No mapper for operation [{$operation->value}]");

        return app($class);
    }
}