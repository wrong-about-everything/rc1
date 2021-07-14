<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot;

use Exception;
use RC\Infrastructure\TelegramBot\AvailableTelegramBotCommands\AvailableTelegramBotCommands as InfrastructureAvailableTelegramBotCommands;
use RC\Infrastructure\TelegramBot\UserCommand\Start;
use RC\Infrastructure\TelegramBot\UserCommand\UserCommand;

class AvailableTelegramBotCommands implements InfrastructureAvailableTelegramBotCommands
{
    public function contain(string $commandName): bool
    {
        return isset($this->list()[$commandName]);
    }

    public function get(string $commandName): UserCommand
    {
        if (!$this->contain($commandName)) {
            throw new Exception(sprintf('Command %s does not exist', $commandName));
        }

        return $this->list()[$commandName];
    }

    private function list()
    {
        return [
            (new Start())->value() => new Start()
        ];
    }
}