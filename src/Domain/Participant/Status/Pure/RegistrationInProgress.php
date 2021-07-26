<?php

declare(strict_types=1);

namespace RC\Domain\Participant\Status\Pure;

class RegistrationInProgress extends Status
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