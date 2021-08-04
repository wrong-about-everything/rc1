<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionName;

class ProductAnalystName extends PositionName
{
    public function value(): string
    {
        return 'Продуктовый аналитик';
    }

    public function exists(): bool
    {
        return true;
    }
}