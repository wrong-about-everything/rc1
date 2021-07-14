<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot;

use RC\Infrastructure\TelegramBot\Method\Method;
use RC\Infrastructure\Http\Request\Url;
use RC\Infrastructure\Http\Request\Url\Query;

class BotApiUrl extends Url
{
    private $method;
    private $query;

    public function __construct(Method $method, Query $query)
    {
        $this->method = $method;
        $this->query = $query;
    }

    public function value(): string
    {
        return
            sprintf(
                'https://api.telegram.org/bot%s/%s?%s',
                getenv('BOT_TOKEN'),
                $this->method->value(),
                $this->query->value()
            );
    }
}