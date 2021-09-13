<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class UserAcquisition extends InterestId
{
    public function value(): int
    {
        return 22;
    }

    public function exists(): bool
    {
        return true;
    }
}