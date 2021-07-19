<?php

declare(strict_types=1);

namespace RC\Domain\UserId;

abstract class UserId
{
    abstract public function value(): string;

    final public function equals(UserId $botId): bool
    {
        return $this->value() === $botId->value();
    }
}