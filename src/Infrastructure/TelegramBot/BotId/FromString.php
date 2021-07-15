<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\BotId;

use RC\Infrastructure\Uuid\UUID;

class FromString extends BotId
{
    private $botId;

    public function __construct(UUID $botId)
    {
        $this->botId = $botId;
    }

    public function value(): string
    {
        return $this->botId->value();
    }
}