<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionId\Pure;

class Marketer extends Position
{
    public function value(): int
    {
        return 6;
    }

    public function exists(): bool
    {
        return true;
    }
}