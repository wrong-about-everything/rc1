<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class ProductDiscovery extends InterestName
{
    public function value(): string
    {
        return 'Product discovery';
    }

    public function exists(): bool
    {
        return true;
    }
}