<?php

declare(strict_types=1);

namespace RC\Domain\TelegramUser\UserId\Pure;

use RC\Domain\TelegramUser\UserId\Impure\TelegramUserId as ImpureTelegramUserId;

class FromImpure extends TelegramUserId
{
    private $telegramUserId;

    public function __construct(ImpureTelegramUserId $telegramUserId)
    {
        $this->telegramUserId = $telegramUserId;
    }

    public function value(): string
    {
        return $this->telegramUserId->value()->pure()->raw();
    }
}