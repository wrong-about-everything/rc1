<?php

declare(strict_types=1);

namespace RC\Domain\Experience\Pure;

class LessThatAYear extends Experience
{
    public function value(): int
    {
        return 0;
    }

    public function exists(): bool
    {
        return true;
    }
}