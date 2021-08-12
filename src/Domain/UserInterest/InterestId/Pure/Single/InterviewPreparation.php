<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class InterviewPreparation extends InterestId
{
    public function value(): int
    {
        return 5;
    }

    public function exists(): bool
    {
        return true;
    }
}