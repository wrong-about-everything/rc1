<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\BotToken\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class BotToken
{
    abstract public function value(): ImpureValue;

    final public function equals(BotToken $botToken): bool
    {
        return $this->value()->pure()->raw() === $botToken->value()->pure()->raw();
    }
}