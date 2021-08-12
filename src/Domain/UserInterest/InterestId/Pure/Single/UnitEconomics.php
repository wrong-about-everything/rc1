<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class UnitEconomics extends InterestId
{
    public function value(): int
    {
        return 15;
    }

    public function exists(): bool
    {
        return true;
    }
}