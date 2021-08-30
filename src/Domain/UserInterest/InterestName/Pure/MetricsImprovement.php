<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class MetricsImprovement extends InterestName
{
    public function value(): string
    {
        return 'Подсчёт и улучшение метрик';
    }

    public function exists(): bool
    {
        return true;
    }
}