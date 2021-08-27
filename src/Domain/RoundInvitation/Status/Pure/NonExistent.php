<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\Status\Pure;

use Exception;

class NonExistent extends Status
{
    public function exists(): bool
    {
        return false;
    }

    public function isFinal(): bool
    {
        return true;
    }

    public function value(): int
    {
        throw new Exception('Invitation status does not exist');
    }
}