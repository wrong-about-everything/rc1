<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage\Pure;

class FromTelegramMessage extends UserMessage
{
    private $concrete;

    public function __construct(string $message)
    {
        $this->concrete = new FromParsedTelegramMessage(json_decode($message, true) ?? []);
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