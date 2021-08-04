<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionName;

class CEOName extends PositionName
{
    public function value(): string
    {
        return 'CEO';
    }

    public function exists(): bool
    {
        return true;
    }
}