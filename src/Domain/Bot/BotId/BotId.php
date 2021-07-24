<?php

declare(strict_types=1);

namespace RC\Domain\Bot\BotId;

abstract class BotId
{
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(BotId $botId): bool
    {
        return $this->value() === $botId->value();
    }
}