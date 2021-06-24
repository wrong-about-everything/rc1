<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Header;

use RC\Infrastructure\Http\Response\Header as HttpResponseHeader;
use RC\Infrastructure\UserStory\Header;

class HttpSpecific extends Header
{
    private $httpResponseHeader;

    public function __construct(HttpResponseHeader $httpResponseHeader)
    {
        $this->httpResponseHeader = $httpResponseHeader;
    }

    public function value(): string
    {
        return $this->httpResponseHeader->value();
    }

    public function isHttpSpecific(): bool
    {
        return true;
    }
}