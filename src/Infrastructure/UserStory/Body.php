<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

abstract class Body
{
    abstract public function value(): string;

    final public function equals(Body $body): bool
    {
        return $this->value() === $body->value();
    }
}