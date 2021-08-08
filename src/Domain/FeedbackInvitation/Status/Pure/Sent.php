<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\Status\Pure;

class Sent extends Status
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