<?php

declare(strict_types=1);

namespace RC\Domain\PositionName;

class ProductManagerName extends PositionName
{
    public function value(): string
    {
        return 'Продакт-менеджер';
    }

    public function exists(): bool
    {
        return true;
    }
}