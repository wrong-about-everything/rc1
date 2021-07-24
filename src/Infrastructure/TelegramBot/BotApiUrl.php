<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot;

use RC\Domain\Bot\BotToken\Pure\BotToken;
use RC\Infrastructure\TelegramBot\Method\Method;
use RC\Infrastructure\Http\Request\Url;
use RC\Infrastructure\Http\Request\Url\Query;

class BotApiUrl extends Url
{
    private $method;
    private $query;
    private $botToken;

    public function __construct(Method $method, Query $query, BotToken $botToken)
    {
        $this->method = $method;
        $this->query = $query;
        $this->botToken = $botToken;
    }

    public function value(): string
    {
        return
            sprintf(
                'https://api.telegram.org/bot%s/%s?%s',
                $this->botToken->value(),
                $this->method->value(),
                $this->query->value()
            );
    }
}