<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\Status\Pure;

class ErrorDuringSending extends Status
{
    public function value(): int
    {
        return 2;
    }

    public function exists(): bool
    {
        return true;
    }
}