<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerId\Pure;

use Exception;

class NonExistent extends BooleanAnswer
{
    public function value(): int
    {
        throw new Exception('This boolean answer does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}