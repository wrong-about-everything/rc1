<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\ChatId;

class FromInteger extends ChatId
{
    private $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function exists(): bool
    {
        return true;
    }
}