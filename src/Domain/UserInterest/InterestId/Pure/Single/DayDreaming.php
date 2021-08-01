<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class DayDreaming extends InterestId
{
    public function value(): int
    {
        return 3;
    }

    public function exists(): bool
    {
        return true;
    }
}