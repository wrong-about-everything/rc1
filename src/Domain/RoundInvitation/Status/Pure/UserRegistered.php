<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\Status\Pure;

class UserRegistered extends Status
{
    public function exists(): bool
    {
        return true;
    }

    public function value(): int
    {
        return 4;
    }
}