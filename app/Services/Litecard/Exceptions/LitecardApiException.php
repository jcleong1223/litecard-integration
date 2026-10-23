<?php

namespace App\Services\Litecard\Exceptions;

use Exception;

class LitecardApiException extends Exception
{
    public function __construct(
        string $message,
        protected int $businessCode = -1,
        protected ?string $requestId = null,
        protected ?int $httpStatus = null,
    ) {
        parent::__construct($message, $businessCode);
    }


    public function getBusinessCode(): int
    {
        return $this->businessCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }
}
