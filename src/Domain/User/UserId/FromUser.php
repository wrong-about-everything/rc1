<?php

declare(strict_types=1);

namespace RC\Domain\User\UserId;

use RC\Domain\User\User;

class FromUser extends UserId
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function value(): string
    {
        return $this->user->value()->pure()->raw()['id'];
    }
}