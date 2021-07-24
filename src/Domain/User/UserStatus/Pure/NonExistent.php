<?php

declare(strict_types=1);

namespace RC\Domain\User\UserStatus\Pure;

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