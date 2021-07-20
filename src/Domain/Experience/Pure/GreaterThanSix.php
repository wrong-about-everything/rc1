<?php

declare(strict_types=1);

namespace RC\Domain\Experience\Pure;

class GreaterThanSix extends Experience
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