<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\AvailableTelegramBotCommands;

use RC\Infrastructure\TelegramBot\UserCommand\UserCommand;

class FromArray implements AvailableTelegramBotCommands
{
    /**
     * @var Map<String, UserCommand> available commands
     */
    private $commands;

    public function __construct(array $commands)
    {
        $this->commands = $commands;
    }

    public function contain(string $commandName): bool
    {
        return isset($this->commands[$commandName]);
    }

    public function get(string $commandName): UserCommand
    {
        return $this->commands[$commandName];
    }
}