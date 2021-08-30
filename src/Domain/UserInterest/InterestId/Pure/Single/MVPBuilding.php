<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class MVPBuilding extends InterestId
{
    public function value(): int
    {
        return 19;
    }

    public function exists(): bool
    {
        return true;
    }
}