<?php

declare(strict_types=1);

namespace RC\Domain\TelegramUser\UserId\Pure;

use RC\Infrastructure\Uuid\UUID;

class FromUuid extends TelegramUserId
{
    private $telegramUserId;

    public function __construct(UUID $telegramUserId)
    {
        $this->telegramUserId = $telegramUserId;
    }

    public function value(): string
    {
        return $this->telegramUserId->value();
    }
}