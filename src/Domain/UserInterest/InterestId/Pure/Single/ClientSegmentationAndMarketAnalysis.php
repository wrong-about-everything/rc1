<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class ClientSegmentationAndMarketAnalysis extends InterestId
{
    public function value(): int
    {
        return 16;
    }

    public function exists(): bool
    {
        return true;
    }
}