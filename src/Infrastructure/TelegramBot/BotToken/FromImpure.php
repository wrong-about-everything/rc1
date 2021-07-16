<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\BotToken;

use Exception;

class FromImpure extends BotToken
{
    private $impureBotToken;

    public function __construct(ImpureBotToken $impureBotToken)
    {
        $this->impureBotToken = $impureBotToken;
    }

    public function value(): string
    {
        if (!$this->impureBotToken->value()->isSuccessful()) {
            throw new Exception('Impure bot token value is not successful');
        }
        if (!$this->impureBotToken->value()->pure()->isPresent()) {
            throw new Exception('Bot token does not exist');
        }

        return $this->impureBotToken->value()->pure()->raw();
    }
}