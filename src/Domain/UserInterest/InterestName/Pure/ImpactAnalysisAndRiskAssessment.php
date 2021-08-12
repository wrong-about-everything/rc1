<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class ImpactAnalysisAndRiskAssessment extends InterestName
{
    public function value(): string
    {
        return 'Импакт-анализ и анализ рисков';
    }

    public function exists(): bool
    {
        return true;
    }
}