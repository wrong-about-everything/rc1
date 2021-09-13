<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class UX extends InterestId
{
    public function value(): int
    {
        return 21;
    }

    public function exists(): bool
    {
        return true;
    }
}