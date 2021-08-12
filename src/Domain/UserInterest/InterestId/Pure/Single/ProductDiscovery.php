<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class ProductDiscovery extends InterestId
{
    public function value(): int
    {
        return 7;
    }

    public function exists(): bool
    {
        return true;
    }
}