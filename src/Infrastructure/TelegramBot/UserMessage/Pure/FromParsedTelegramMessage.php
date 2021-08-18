<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage\Pure;

class FromParsedTelegramMessage extends UserMessage
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

    public function value(): string
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }
}