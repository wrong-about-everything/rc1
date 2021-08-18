<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage\Pure;

abstract class UserMessage
{
    /**
     * A message that user sent.
     */
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(UserMessage $userMessage): bool
    {
        return $this->value() === $userMessage->value();
    }
}