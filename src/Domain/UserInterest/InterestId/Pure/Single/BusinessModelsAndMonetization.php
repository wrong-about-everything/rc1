<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class BusinessModelsAndMonetization extends InterestId
{
    public function value(): int
    {
        return 24;
    }

    public function exists(): bool
    {
        return true;
    }
}