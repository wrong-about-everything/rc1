<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\ChatId;

class FromMessage extends ChatId
{
    private $concrete;

    public function __construct(array $message)
    {
        $this->concrete =
            isset($message['message']['chat']['id'])
                ? new FromInteger($message['message']['chat']['id'])
                : new NonExistent();
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }
}