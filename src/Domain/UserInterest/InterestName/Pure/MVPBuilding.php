<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class MVPBuilding extends InterestName
{
    public function value(): string
    {
        return 'Построение MVP';
    }

    public function exists(): bool
    {
        return true;
    }
}