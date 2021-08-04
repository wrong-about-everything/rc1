<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionId\Pure;

class ProductAnalyst extends Position
{
    public function value(): int
    {
        return 5;
    }

    public function exists(): bool
    {
        return true;
    }
}