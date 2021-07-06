<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Method;

class SendMessage extends Method
{
    public function value(): string
    {
        return 'sendMessage';
    }
}