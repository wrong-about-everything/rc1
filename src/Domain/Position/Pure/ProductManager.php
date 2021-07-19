<?php

declare(strict_types=1);

namespace RC\Domain\Position\Pure;

class ProductManager extends Position
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