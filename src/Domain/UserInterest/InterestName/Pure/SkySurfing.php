<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class SkySurfing extends InterestName
{
    public function value(): string
    {
        return 'Sky surfing';
    }

    public function exists(): bool
    {
        return true;
    }
}