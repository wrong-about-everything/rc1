<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\BotToken\Pure;

abstract class BotToken
{
    abstract public function value(): string;

    final public function equals(BotToken $botToken): bool
    {
        return $this->value() === $botToken->value();
    }
}