<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonExistent implements UserMessage
{
    public function value(): ImpureValue
    {
        throw new Exception('User message does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}