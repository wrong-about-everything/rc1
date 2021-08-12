<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class CareerBuilding extends InterestName
{
    public function value(): string
    {
        return 'Построение карьеры';
    }

    public function exists(): bool
    {
        return true;
    }
}