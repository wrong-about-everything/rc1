<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class CareerLevelUp extends InterestId
{
    public function value(): int
    {
        return 12;
    }

    public function exists(): bool
    {
        return true;
    }
}