<?php

declare(strict_types=1);

namespace RC\Domain\UserStatus;

use Exception;

class NonExistent extends UserStatus
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