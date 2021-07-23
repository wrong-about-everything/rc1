<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromParsedTelegramMessage implements UserMessage
{
    private $concrete;

    public function __construct(array $message)
    {
        $this->concrete =
            ($message['message']['entities'][0]['type'] ?? '') !== 'bot_command'
                ? isset($message['message']['text']) ? new FromString($message['message']['text']) : new NonExistent()
                : new NonExistent()
        ;
    }

    public function value(): ImpureValue
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }
}