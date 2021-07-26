<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class Networking extends InterestName
{
    public function value(): string
    {
        return 'Нетворкинг без определенной темы';
    }

    public function exists(): bool
    {
        return true;
    }
}