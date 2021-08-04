<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionId\Pure;

class ProjectManager extends Position
{
    public function value(): int
    {
        return 4;
    }

    public function exists(): bool
    {
        return true;
    }
}