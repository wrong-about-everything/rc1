<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromTelegramMessage implements UserMessage
{
    private $concrete;

    public function __construct(string $message)
    {
        $this->concrete = new FromParsedTelegramMessage(json_decode($message, true));
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