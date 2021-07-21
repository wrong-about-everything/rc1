<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionName;

class ProductDesignerName extends PositionName
{
    public function value(): string
    {
        return 'Продуктовый дизайнер';
    }

    public function exists(): bool
    {
        return true;
    }
}