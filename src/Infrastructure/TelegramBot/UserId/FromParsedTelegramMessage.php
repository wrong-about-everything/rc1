<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId;

class FromParsedTelegramMessage extends UserId
{
    private $concrete;

    public function __construct(array $message)
    {
        $this->concrete =
            isset($message['message']['from']['id'])
                ? new FromInteger($message['message']['from']['id'])
                : new NonExistent()
        ;
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