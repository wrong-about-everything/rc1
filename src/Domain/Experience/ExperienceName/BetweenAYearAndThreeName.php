<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceName;

class BetweenAYearAndThreeName extends ExperienceName
{
    public function value(): string
    {
        return 'От года до трёх лет';
    }

    public function exists(): bool
    {
        return true;
    }
}