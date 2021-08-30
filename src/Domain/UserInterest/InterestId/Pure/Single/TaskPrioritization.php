<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class TaskPrioritization extends InterestId
{
    public function value(): int
    {
        return 18;
    }

    public function exists(): bool
    {
        return true;
    }
}