<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerId\Pure;

class Yes extends BooleanAnswer
{
    public function value(): int
    {
        return 1;
    }

    public function exists(): bool
    {
        return true;
    }
}