<?php

declare(strict_types=1);

namespace RC\Domain\User\UserId;

use RC\Infrastructure\Uuid\UUID;

class FromUuid extends UserId
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