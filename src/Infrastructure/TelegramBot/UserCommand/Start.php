<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserCommand;

class Start extends UserCommand
{
    public function value(): string
    {
        return '/start';
    }

    public function exists(): bool
    {
        return true;
    }
}