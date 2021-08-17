<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerName;

class Yes extends BooleanAnswerName
{
    public function value(): string
    {
        return 'Да';
    }

    public function exists(): bool
    {
        return true;
    }
}