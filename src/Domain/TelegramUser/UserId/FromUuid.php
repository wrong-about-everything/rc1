<?php

declare(strict_types=1);

namespace RC\Domain\TelegramUser\UserId;

use RC\Infrastructure\Uuid\UUID;

class FromUuid extends TelegramUserId
{
    private $userId;

    public function __construct(UUID $telegramUserId)
    {
        $this->userId = $telegramUserId;
    }

    public function value(): string
    {
        return $this->userId->value();
    }
}