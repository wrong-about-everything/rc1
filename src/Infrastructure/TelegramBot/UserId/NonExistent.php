<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId;

use Exception;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class NonExistent extends TelegramUserId
{
    public function value(): ImpureValue
    {
        throw new Exception('User id does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}