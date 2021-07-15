<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\BotToken;

class FromImpure extends BotToken
{
    private $impureBotToken;

    public function __construct(ImpureBotToken $impureBotToken)
    {
        $this->impureBotToken = $impureBotToken;
    }

    public function value(): string
    {
        return $this->impureBotToken->value()->pure()->raw();
    }
}