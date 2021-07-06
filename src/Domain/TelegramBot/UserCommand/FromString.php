<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\UserCommand;

use RC\Infrastructure\TelegramBot\UserCommand\FromString as UserCommandFromString;
use RC\Infrastructure\TelegramBot\UserCommand\UserCommand;

class FromString extends UserCommand
{
    private $concrete;

    public function __construct(string $commandName)
    {
        $this->concrete = new UserCommandFromString($commandName, new AvailableTelegramBotCommands());
    }

    public function value(): string
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }
}