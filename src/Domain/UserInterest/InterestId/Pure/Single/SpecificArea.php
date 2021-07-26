<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class SpecificArea extends InterestId
{
    public function value(): int
    {
        return 1;
    }

    public function exists(): bool
    {
        return true;
    }
}