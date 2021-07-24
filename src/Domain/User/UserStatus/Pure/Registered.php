<?php

declare(strict_types=1);

namespace RC\Domain\User\UserStatus\Pure;

class Registered extends UserStatus
{
    public function value(): int
    {
        return 10;
    }

    public function exists(): bool
    {
        return true;
    }
}