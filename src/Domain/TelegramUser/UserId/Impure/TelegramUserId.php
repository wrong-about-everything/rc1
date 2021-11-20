<?php

declare(strict_types=1);

namespace RC\Domain\TelegramUser\UserId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class TelegramUserId
{
    abstract public function value(): ImpureValue;

    final public function equals(TelegramUserId $botId): bool
    {
        return $this->value()->pure()->raw() === $botId->value()->pure()->raw();
    }
}