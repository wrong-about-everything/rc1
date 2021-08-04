<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionName;

class SystemOrBusinessAnalystName extends PositionName
{
    public function value(): string
    {
        return 'Системный/бизнес-аналитик';
    }

    public function exists(): bool
    {
        return true;
    }
}