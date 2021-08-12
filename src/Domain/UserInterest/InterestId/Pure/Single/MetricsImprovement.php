<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class MetricsImprovement extends InterestId
{
    public function value(): int
    {
        return 9;
    }

    public function exists(): bool
    {
        return true;
    }
}