<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\ChatId;

use Exception;

class NonExistent extends ChatId
{
    public function value(): int
    {
        throw new Exception('Chat id does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}