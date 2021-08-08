<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\Status\Pure;

class Accepted extends Status
{
    public function value(): int
    {
        return 3;
    }

    public function exists(): bool
    {
        return true;
    }
}