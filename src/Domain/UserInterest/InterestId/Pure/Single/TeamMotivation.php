<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class TeamMotivation extends InterestId
{
    public function value(): int
    {
        return 8;
    }

    public function exists(): bool
    {
        return true;
    }
}