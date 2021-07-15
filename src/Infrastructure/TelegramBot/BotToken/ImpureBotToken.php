<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\BotToken;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class ImpureBotToken
{
    abstract public function value(): ImpureValue;

    final public function equals(ImpureBotToken $botToken): bool
    {
        return $this->value()->pure()->raw() === $botToken->value()->pure()->raw();
    }
}