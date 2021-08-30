<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class AllThingsDevelopment extends InterestName
{
    public function value(): string
    {
        return 'Разработка и её процессы: ускорение, управление, планирование';
    }

    public function exists(): bool
    {
        return true;
    }
}