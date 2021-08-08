<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure;

use Exception;

class NonExistent implements FeedbackInvitationId
{
    public function value(): string
    {
        throw new Exception('This feedback invitation id does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}