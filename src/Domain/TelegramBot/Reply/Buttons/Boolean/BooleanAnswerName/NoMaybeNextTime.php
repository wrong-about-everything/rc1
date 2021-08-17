<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerName;

class NoMaybeNextTime extends BooleanAnswerName
{
    public function value(): string
    {
        return 'Нет, давайте в следующий раз';
    }

    public function exists(): bool
    {
        return true;
    }
}