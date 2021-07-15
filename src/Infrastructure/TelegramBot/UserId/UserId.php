<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId;

abstract class UserId
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equal(UserId $userId): bool
    {
        return $this->value() === $userId->value();
    }
}