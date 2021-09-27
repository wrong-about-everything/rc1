<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class Sales extends InterestId
{
    public function value(): int
    {
        return 23;
    }

    public function exists(): bool
    {
        return true;
    }
}