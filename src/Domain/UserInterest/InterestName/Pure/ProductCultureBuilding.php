<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class ProductCultureBuilding extends InterestName
{
    public function value(): string
    {
        return 'Построение продуктовой культуры';
    }

    public function exists(): bool
    {
        return true;
    }
}