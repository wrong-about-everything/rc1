<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class DayDreaming extends InterestName
{
    public function value(): string
    {
        return 'Daydreaming';
    }

    public function exists(): bool
    {
        return true;
    }
}