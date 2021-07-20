<?php

declare(strict_types=1);

namespace RC\Domain\PositionName;

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