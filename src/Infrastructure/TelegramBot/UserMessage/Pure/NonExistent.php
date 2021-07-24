<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage\Pure;

use Exception;

class NonExistent implements UserMessage
{
    public function value(): string
    {
        throw new Exception('User message does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}