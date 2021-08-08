<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\Status\Pure;

class Declined extends Status
{
    public function value(): int
    {
        return 4;
    }

    public function exists(): bool
    {
        return true;
    }
}