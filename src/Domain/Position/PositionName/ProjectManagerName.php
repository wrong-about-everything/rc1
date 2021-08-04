<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionName;

class ProjectManagerName extends PositionName
{
    public function value(): string
    {
        return 'Проджект-менеджер';
    }

    public function exists(): bool
    {
        return true;
    }
}