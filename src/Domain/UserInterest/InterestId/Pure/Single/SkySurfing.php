<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class SkySurfing extends InterestId
{
    public function value(): int
    {
        return 2;
    }

    public function exists(): bool
    {
        return true;
    }
}