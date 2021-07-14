<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\AvailableTelegramBotCommands;

use RC\Infrastructure\TelegramBot\UserCommand\UserCommand;

interface AvailableTelegramBotCommands
{
    public function contain(string $commandName): bool;

    public function get(string $commandName): UserCommand;
}