<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class Sales extends InterestName
{
    public function value(): string
    {
        return 'Продажи';
    }

    public function exists(): bool
    {
        return true;
    }
}