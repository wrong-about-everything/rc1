<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class BusinessModelsAndMonetization extends InterestName
{
    public function value(): string
    {
        return 'Бизнес-модели и монетизация';
    }

    public function exists(): bool
    {
        return true;
    }
}