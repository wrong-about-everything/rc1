<?php

declare(strict_types=1);

namespace RC\Domain\Participant\Status\Pure;

use Exception;

class NonExistent extends Status
{
    public function value(): int
    {
        throw new Exception('Status does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}