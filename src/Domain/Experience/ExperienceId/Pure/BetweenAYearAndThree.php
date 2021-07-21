<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceId\Pure;

class BetweenAYearAndThree extends Experience
{
    public function value(): int
    {
        return 1;
    }

    public function exists(): bool
    {
        return true;
    }
}