<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

use RC\Infrastructure\ImpureInteractions\PureValue;

abstract class Body
{
    abstract public function value(): PureValue;

    final public function equals(Body $body): bool
    {
        return $this->value() === $body->value();
    }
}