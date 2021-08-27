<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\Status\Pure;

class Sent extends Status
{
    public function exists(): bool
    {
        return true;
    }

    public function isFinal(): bool
    {
        return false;
    }

    public function value(): int
    {
        return 1;
    }
}