<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class TelegramUserId
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): bool;

    final public function equal(TelegramUserId $userId): bool
    {
        return $this->value()->pure()->raw() === $userId->value()->pure()->raw();
    }
}