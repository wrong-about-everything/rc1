<?php

declare(strict_types=1);

namespace RC\Domain\TelegramUser\UserId\Pure;

use Ramsey\Uuid\Uuid as RamseyUuid;

class Random extends TelegramUserId
{
    private $telegramUserId;

    public function __construct()
    {
        $this->telegramUserId = RamseyUuid::uuid4()->toString();
    }

    public function value(): string
    {
        return $this->telegramUserId;
    }
}