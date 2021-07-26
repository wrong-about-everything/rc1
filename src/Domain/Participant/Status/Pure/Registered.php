<?php

declare(strict_types=1);

namespace RC\Domain\Participant\Status\Pure;

class Registered extends Status
{
    public function value(): int
    {
        return 1;
    }

    public function exists(): bool
    {
        return true;
    }
}