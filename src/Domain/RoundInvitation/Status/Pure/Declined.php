<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\Status\Pure;

class Declined extends Status
{
    public function exists(): bool
    {
        return true;
    }

    public function isFinal(): bool
    {
        return true;
    }

    public function value(): int
    {
        return 4;
    }
}