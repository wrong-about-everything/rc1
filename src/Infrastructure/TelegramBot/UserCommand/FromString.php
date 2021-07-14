<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserCommand;

use Exception;
use RC\Infrastructure\TelegramBot\AvailableTelegramBotCommands\AvailableTelegramBotCommands;

class FromString extends UserCommand
{
    private $commandName;
    private $availableCommands;

    public function __construct(string $commandName, AvailableTelegramBotCommands $availableCommands)
    {
        $this->commandName = $commandName;
        $this->availableCommands = $availableCommands;
    }

    public function value(): string
    {
        if (!$this->exists()) {
            throw new Exception(sprintf('Command %s does not exist', $this->commandName));
        }

        return $this->availableCommands->get($this->commandName)->value();
    }

    public function exists(): bool
    {
        return $this->availableCommands->contain($this->commandName);
    }
}