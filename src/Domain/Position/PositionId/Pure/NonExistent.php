<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionId\Pure;

use Exception;

class NonExistent extends Position
{
    public function value(): int
    {
        throw new Exception('This position does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}