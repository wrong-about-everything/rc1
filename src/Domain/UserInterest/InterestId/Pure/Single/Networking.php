<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class Networking extends InterestId
{
    public function value(): int
    {
        return 0;
    }

    public function exists(): bool
    {
        return true;
    }
}