<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions\PureValue;

use RC\Infrastructure\ImpureInteractions\PureValue;

class Present implements PureValue
{
    private $raw;

    public function __construct($raw)
    {
        $this->raw = $raw;
    }

    public function isPresent(): bool
    {
        return true;
    }

    public function raw()
    {
        return $this->raw;
    }
}