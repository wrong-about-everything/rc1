<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class CareerLevelUp extends InterestName
{
    public function value(): string
    {
        return 'Карьерный рост';
    }

    public function exists(): bool
    {
        return true;
    }
}