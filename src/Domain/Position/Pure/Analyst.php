<?php

declare(strict_types=1);

namespace RC\Domain\Position\Pure;

class Analyst extends Position
{
    public function value(): int
    {
        return 1;
    }

    public function exists(): bool
    {
        return true;
    }
}