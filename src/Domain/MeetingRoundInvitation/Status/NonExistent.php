<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRoundInvitation\Status;

use Exception;

class NonExistent extends Status
{
    public function exists(): bool
    {
        return false;
    }

    public function value(): int
    {
        return throw new Exception('Invitation status does not exist');
    }
}