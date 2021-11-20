<?php

declare(strict_types=1);

namespace RC\Domain\BotUser\Id\Pure;

use Ramsey\Uuid\Uuid;

class Random implements BotUserId
{
    private $uuid;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4()->toString();
    }

    public function value(): string
    {
        return $this->uuid;
    }
}