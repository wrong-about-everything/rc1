<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class TeamManagement extends InterestName
{
    public function value(): string
    {
        return 'Управление командой';
    }

    public function exists(): bool
    {
        return true;
    }
}