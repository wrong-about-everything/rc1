<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserCommand;

interface AvailableTelegramBotCommands
{
    public function contain(string $commandName): bool;

    public function get(string $commandName): UserCommand;
}