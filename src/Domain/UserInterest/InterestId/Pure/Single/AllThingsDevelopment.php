<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class AllThingsDevelopment extends InterestId
{
    public function value(): int
    {
        return 20;
    }

    public function exists(): bool
    {
        return true;
    }
}