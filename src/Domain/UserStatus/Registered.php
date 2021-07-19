<?php

declare(strict_types=1);

namespace RC\Domain\UserStatus;

class Registered extends UserStatus
{
    public function value(): int
    {
        return 0;
    }

    public function exists(): bool
    {
        return true;
    }
}