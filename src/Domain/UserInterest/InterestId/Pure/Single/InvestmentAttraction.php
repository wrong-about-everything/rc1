<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class InvestmentAttraction extends InterestId
{
    public function value(): int
    {
        return 25;
    }

    public function exists(): bool
    {
        return true;
    }
}