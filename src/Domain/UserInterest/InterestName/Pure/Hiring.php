<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class Hiring extends InterestName
{
    public function value(): string
    {
        return 'Найм сотрудников';
    }

    public function exists(): bool
    {
        return true;
    }
}