<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRoundInvitation\Status;

class Sent extends Status
{
    public function exists(): bool
    {
        return true;
    }

    public function value(): int
    {
        return 1;
    }
}