<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Url\Port;

use RC\Infrastructure\Http\Request\Url\Port;

class FromInt implements Port
{
    private $port;

    public function __construct(int $port)
    {
        $this->port = $port;
    }

    public function value(): int
    {
        return $this->port;
    }

    public function isSpecified(): bool
    {
        return true;
    }
}
