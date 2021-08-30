<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class Strategy extends InterestId
{
    public function value(): int
    {
        return 17;
    }

    public function exists(): bool
    {
        return true;
    }
}