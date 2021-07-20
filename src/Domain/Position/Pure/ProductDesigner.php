<?php

declare(strict_types=1);

namespace RC\Domain\Position\Pure;

class ProductDesigner extends Position
{
    public function value(): int
    {
        return 2;
    }

    public function exists(): bool
    {
        return true;
    }
}