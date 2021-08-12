<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class Hiring extends InterestId
{
    public function value(): int
    {
        return 11;
    }

    public function exists(): bool
    {
        return true;
    }
}