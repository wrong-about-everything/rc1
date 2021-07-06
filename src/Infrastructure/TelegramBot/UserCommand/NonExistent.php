<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserCommand;

use Exception;

class NonExistent extends UserCommand
{
    public function value(): string
    {
        throw new Exception('Command you are looking for does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}