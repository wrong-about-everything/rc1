<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionName;

use Exception;
use RC\Domain\Position\PositionId\Pure\Position;

class NonExistent extends PositionName
{
    public function value(): string
    {
        throw new Exception('Position name does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}