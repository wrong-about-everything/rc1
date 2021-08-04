<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionId\Pure;

class CEO extends Position
{
    public function value(): int
    {
        return 3;
    }

    public function exists(): bool
    {
        return true;
    }
}