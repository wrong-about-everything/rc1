<?php

declare(strict_types=1);

namespace RC\Domain\TelegramUser\UserId;

use RC\Infrastructure\Uuid\UUID;

class FromUuid extends TelegramUserId
{
    private $userId;

    public function __construct(UUID $botId)
    {
        $this->userId = $botId;
    }

    public function value(): string
    {
        return $this->userId->value();
    }
}