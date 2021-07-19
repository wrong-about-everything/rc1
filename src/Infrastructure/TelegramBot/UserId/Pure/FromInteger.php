<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId\Pure;

class FromInteger extends TelegramUserId
{
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function value(): int
    {
        return $this->id;
    }

    public function exists(): bool
    {
        return true;
    }
}