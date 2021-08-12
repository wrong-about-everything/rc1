<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class ImpactAnalysisAndRiskAssessment extends InterestId
{
    public function value(): int
    {
        return 4;
    }

    public function exists(): bool
    {
        return true;
    }
}