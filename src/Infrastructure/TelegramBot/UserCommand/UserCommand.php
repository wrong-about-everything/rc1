<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserCommand;

abstract class UserCommand
{
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(UserCommand $command): bool
    {
        return $this->value() === $command->value();
    }
}