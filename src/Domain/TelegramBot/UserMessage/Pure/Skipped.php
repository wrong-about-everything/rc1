<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\UserMessage\Pure;

use RC\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;

class Skipped extends UserMessage
{
    public function value(): string
    {
        return 'Пропустить';
    }

    public function exists(): bool
    {
        return true;
    }
}