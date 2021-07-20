<?php

declare(strict_types=1);

namespace RC\Domain\PositionName;

use Exception;
use RC\Domain\Position\Pure\Position;

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