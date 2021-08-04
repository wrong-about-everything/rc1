<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionName;

class MarketerName extends PositionName
{
    public function value(): string
    {
        return 'Маркетолог';
    }

    public function exists(): bool
    {
        return true;
    }
}