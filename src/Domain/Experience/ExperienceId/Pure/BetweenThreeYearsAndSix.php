<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceId\Pure;

class BetweenThreeYearsAndSix extends Experience
{
    public function value(): int
    {
        return 2;
    }

    public function exists(): bool
    {
        return true;
    }
}