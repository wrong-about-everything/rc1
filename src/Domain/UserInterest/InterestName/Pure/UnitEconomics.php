<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class UnitEconomics extends InterestName
{
    public function value(): string
    {
        return 'Юнит-экономика';
    }

    public function exists(): bool
    {
        return true;
    }
}