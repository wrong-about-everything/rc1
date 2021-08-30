<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class Strategy extends InterestName
{
    public function value(): string
    {
        return 'Стратегия';
    }

    public function exists(): bool
    {
        return true;
    }
}