<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

abstract class Error
{
    abstract public function value(): int;

    public function equals(Error $error): bool
    {
        return $this->value() === $error->value();
    }
}