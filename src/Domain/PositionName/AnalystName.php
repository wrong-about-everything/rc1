<?php

declare(strict_types=1);

namespace RC\Domain\PositionName;

class AnalystName extends PositionName
{
    public function value(): string
    {
        return 'Аналитик';
    }

    public function exists(): bool
    {
        return true;
    }
}