<?php

declare(strict_types=1);

namespace RC\Domain\BotId;

abstract class BotId
{
    abstract public function value(): string;

    final public function equals(BotId $botId): bool
    {
        return $this->value() === $botId->value();
    }
}