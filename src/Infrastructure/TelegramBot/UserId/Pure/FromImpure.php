<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId\Pure;

use RC\Infrastructure\TelegramBot\UserId\Impure\TelegramUserId as ImpureTelegramUserId;

class FromImpure extends TelegramUserId
{
    private $impureTelegramUserId;

    public function __construct(ImpureTelegramUserId $impureTelegramUserId)
    {
        $this->impureTelegramUserId = $impureTelegramUserId;
    }

    public function value(): int
    {
        return $this->impureTelegramUserId->value()->pure()->raw();
    }

    public function exists(): bool
    {
        return true;
    }
}