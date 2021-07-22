<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\BotToken\Pure;

class FromString extends BotToken
{
    private $botToken;

    public function __construct(string $botToken)
    {
        $this->botToken = $botToken;
    }

    public function value(): string
    {
        return $this->botToken;
    }
}