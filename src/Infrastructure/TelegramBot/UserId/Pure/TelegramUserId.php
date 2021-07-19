<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId\Pure;

abstract class TelegramUserId
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equal(TelegramUserId $userId): bool
    {
        return $this->value() === $userId->value();
    }
}