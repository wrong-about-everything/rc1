<?php

declare(strict_types=1);

namespace RC\Domain\ExperienceName;

class BetweenThreeYearsAndSixName extends ExperienceName
{
    public function value(): string
    {
        return 'От трёх лет до шести';
    }

    public function exists(): bool
    {
        return true;
    }
}