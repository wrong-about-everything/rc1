<?php

declare(strict_types=1);

namespace RC\Domain\TelegramUser\UserId;

use RC\Domain\TelegramUser\TelegramUser;

class FromUser extends UserId
{
    private $user;

    public function __construct(TelegramUser $user)
    {
        $this->user = $user;
    }

    public function value(): string
    {
        return $this->user->value()->pure()->raw()['id'];
    }
}