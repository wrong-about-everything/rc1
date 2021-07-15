<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\ChatId;

abstract class ChatId
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(ChatId $chatId): bool
    {
        return $this->value() === $chatId->value();
    }
}