<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId;

use Exception;

class NonExistent extends UserId
{
    public function value(): int
    {
        throw new Exception('User id does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}