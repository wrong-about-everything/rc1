<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class UX extends InterestName
{
    public function value(): string
    {
        return 'UX';
    }

    public function exists(): bool
    {
        return true;
    }
}