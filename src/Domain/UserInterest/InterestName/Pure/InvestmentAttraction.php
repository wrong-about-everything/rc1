<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class InvestmentAttraction extends InterestName
{
    public function value(): string
    {
        return 'Привлечение инвестиций';
    }

    public function exists(): bool
    {
        return true;
    }
}