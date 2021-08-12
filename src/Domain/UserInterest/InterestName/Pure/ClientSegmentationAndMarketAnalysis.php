<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class ClientSegmentationAndMarketAnalysis extends InterestName
{
    public function value(): string
    {
        return 'Сегментация клиентов и анализ рынка';
    }

    public function exists(): bool
    {
        return true;
    }
}