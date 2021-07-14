<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\Method;

class SendMessage extends Method
{
    public function value(): string
    {
        return 'sendMessage';
    }
}