<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceId\Pure;

class LessThanAYear extends Experience
{
    public function value(): int
    {
        return 0;
    }

    public function exists(): bool
    {
        return true;
    }
}