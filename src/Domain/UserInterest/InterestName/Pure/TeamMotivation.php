<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class TeamMotivation extends InterestName
{
    public function value(): string
    {
        return 'Мотивация команды';
    }

    public function exists(): bool
    {
        return true;
    }
}